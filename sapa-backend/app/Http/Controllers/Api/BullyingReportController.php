<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Report\StoreBullyingReportRequest;
use App\Models\Report;
use Illuminate\Http\Request;
use App\Services\NotificationService;
use App\Http\Resources\BullyingQueueResource;

class BullyingReportController extends Controller
{
    /**
     * HANYA counselor — sudah dijaga middleware permission:bullying.handle
     * di route, tapi tetap authorize() di sini sebagai lapis kedua.
     */
    public function index(Request $request)
    {
        $reports = Report::query()
            ->ofType('bullying')
            ->with(['bullyingDetail']) // TIDAK load 'reporter' — jaga anonimitas di list
            ->latest()
            ->paginate(15);

        return response()->json($reports);
    }

    public function queue(Request $request)
    {
        $query = Report::query()
            ->ofType('bullying')
            ->with(['bullyingDetail']);

        // Filter status (opsional dari query string ?status=pending)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            // default: tampilkan yang masih aktif
            $query->whereIn('status', ['pending', 'reviewing', 'in_progress']);
        }

        // Filter rentang tanggal (opsional)
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $reports = $query->latest()->paginate(15);

        return BullyingQueueResource::collection($reports);
    }

    public function store(StoreBullyingReportRequest $request, NotificationService $notificationService)
    {
        $validated = $request->validated();
        $user = $request->user();

        $report = Report::create([
            'reporter_id' => $validated['is_anonymous'] ? null : $user->id,
            'type' => 'bullying',
            'title' => 'Laporan Bullying', // generic, tidak dari input user
            'description' => $validated['description'],
            'is_anonymous' => $validated['is_anonymous'],
            'status' => 'pending',
            'priority' => 'high', // bullying otomatis prioritas tinggi
        ]);

        $report->bullyingDetail()->create([
            'reporter_relation' => $validated['reporter_relation'],
            'incident_date' => $validated['incident_date'] ?? null,
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('report-attachments/bullying', 'public'); // folder terpisah
                $report->attachments()->create([
                    'file_path' => $path,
                    'file_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize() / 1024,
                ]);
            }
        }

        $report->statusLogs()->create([
            'old_status' => null,
            'new_status' => 'pending',
            'changed_by' => $user->id,
            'note' => 'Laporan bullying diajukan.',
        ]);

        $notificationService->notifyNewReport($report);

        return response()->json([
            'message' => 'Laporan kamu telah diterima dan akan ditangani oleh Guru BK secara rahasia.',
            'report_code' => $report->report_code, // siswa simpan kode ini untuk tracking
        ], 201);
    }

    public function handle(Request $request, Report $report)
    {
        $this->authorize('updateStatus', $report);

        $validated = $request->validate([
            'status' => 'required|in:reviewing,in_progress,resolved,rejected',
            'handling_notes' => 'nullable|string|max:2000',
        ]);

        $oldStatus = $report->status;

        $report->update([
            'status' => $validated['status'],
            'resolved_at' => $validated['status'] === 'resolved' ? now() : null,
        ]);

        $report->bullyingDetail()->update([
            'handled_by_counselor_id' => $request->user()->id,
            'handling_notes' => $validated['handling_notes'] ?? null,
        ]);

        $report->statusLogs()->create([
            'old_status' => $oldStatus,
            'new_status' => $validated['status'],
            'changed_by' => $request->user()->id,
            'note' => 'Ditangani oleh Guru BK.',
        ]);

        return response()->json([
            'message' => 'Status laporan berhasil diperbarui.',
            'report' => $report->fresh()->load('bullyingDetail'),
        ]);
    }

    public function stats(Request $request)
    {
        $this->authorize('viewAny', Report::class);

        $stats = Report::ofType('bullying')
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return response()->json([
            'pending' => $stats['pending'] ?? 0,
            'reviewing' => $stats['reviewing'] ?? 0,
            'in_progress' => $stats['in_progress'] ?? 0,
            'resolved' => $stats['resolved'] ?? 0,
            'rejected' => $stats['rejected'] ?? 0,
            'total' => $stats->sum(),
        ]);
    }

    public function revealIdentity(Request $request, Report $report)
    {
        $this->authorize('updateStatus', $report); // pakai policy yang sama

        if ($report->type !== 'bullying' || $report->is_anonymous) {
            return response()->json(['message' => 'Identitas tidak dapat dibuka untuk laporan ini.'], 422);
        }

        // Catat siapa yang membuka identitas — akuntabilitas penting untuk data sensitif
        $report->statusLogs()->create([
            'old_status' => $report->status,
            'new_status' => $report->status, // status tidak berubah, hanya aksi dicatat
            'changed_by' => $request->user()->id,
            'note' => 'Identitas pelapor dibuka oleh BK untuk keperluan penanganan.',
        ]);

        $report->load('reporter:id,name,class_name,phone');

        return response()->json([
            'reporter' => $report->reporter,
        ]);
    }
}
