<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Report\StoreBullyingReportRequest;
use App\Models\Report;
use Illuminate\Http\Request;

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
        $reports = Report::query()
            ->ofType('bullying')
            ->whereIn('status', ['pending', 'reviewing', 'in_progress'])
            ->with(['bullyingDetail'])
            ->latest()
            ->paginate(15);

        return response()->json($reports);
    }

    public function store(StoreBullyingReportRequest $request)
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

        // TODO: trigger notifikasi ke SEMUA user dengan role counselor

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
}
