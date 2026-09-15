<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Report\StoreBullyingReportRequest;
use App\Models\Report;
use Illuminate\Http\Request;
use App\Services\NotificationService;
use App\Http\Resources\BullyingQueueResource;
use Illuminate\Support\Facades\DB;

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

    public function show(Request $request, Report $report)
    {
        // Memastikan laporan yang diakses berjenis 'bullying'
        if ($report->type !== 'bullying') {
            return response()->json(['message' => 'Laporan bukan merupakan laporan perundungan.'], 404);
        }

        // Jalankan pemeriksaan Policy
        $this->authorize('view', $report);

        // Load relasi yang dibutuhkan untuk halaman detail counselor
        $report->load([
            'bullyingDetail',
            'attachments',
            'statusLogs.changedBy:id,name',
            'reporter:id,name,email', // Reporter akan null jika is_anonymous = true
        ]);

        return response()->json([
            'data' => $report
        ]);
    }

    // BullyingReportController.php
    public function queue(Request $request)
    {
        $this->authorize('viewAny', Report::class);

        $query = Report::query()
            ->ofType('bullying')
            ->with(['bullyingDetail.counselor:id,name', 'reporter:id,name,class_name']);

        // Dukung filter status tunggal ATAU multi (status[]=resolved&status[]=rejected)
        if ($request->filled('status')) {
            $statuses = (array) $request->status;
            $query->whereIn('status', $statuses);
        } else {
            $query->whereIn('status', ['pending', 'reviewing', 'in_progress']);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $reports = $query->latest()->paginate(50); // arsip biasanya butuh limit lebih besar

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
        // 1. Otorisasi - pastikan user adalah BK/Counselor dan laporan berjenis 'bullying'
        $this->authorize('updateStatus', $report);

        if ($report->type !== 'bullying') {
            return response()->json([
                'message' => 'Laporan ini bukan merupakan laporan perundungan.'
            ], 422);
        }

        // 2. Validasi Input dari form Frontend
        $validated = $request->validate([
            'status' => 'required|string|in:reviewing,in_progress,resolved,rejected',
            'handling_notes' => 'required|string|max:2000',
        ], [
            'status.required' => 'Status penanganan wajib dipilih.',
            'status.in' => 'Status yang dipilih tidak valid.',
            'handling_notes.required' => 'Catatan penanganan wajib diisi.',
        ]);

        $oldStatus = $report->status;
        $newStatus = $validated['status'];

        // 3. Eksekusi Update via DB Transaction
        DB::transaction(function () use ($report, $oldStatus, $newStatus, $validated, $request) {
            // Update data utama di tabel reports
            $report->update([
                'status' => $newStatus,
                'resolved_at' => $newStatus === 'resolved' ? now() : null,
            ]);

            // Update detail khusus perundungan (handling_notes & id konselor)
            $report->bullyingDetail()->updateOrCreate(
                ['report_id' => $report->id],
                [
                    'handled_by_counselor_id' => $request->user()->id,
                    'handling_notes' => $validated['handling_notes'],
                ]
            );

            // Catat riwayat perubahan ke tabel status_logs
            $report->statusLogs()->create([
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'changed_by' => $request->user()->id,
                'note' => $validated['handling_notes'],
            ]);
        });

        // 4. Return Response JSON beserta relasi terbarunya
        return response()->json([
            'message' => 'Penanganan laporan berhasil diperbarui.',
            'data' => $report->fresh()->load([
                'bullyingDetail',
                'statusLogs.changedBy:id,name',
            ]),
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
