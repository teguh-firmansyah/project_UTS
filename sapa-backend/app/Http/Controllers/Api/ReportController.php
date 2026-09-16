<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReportDetailResource;
use App\Http\Resources\ReportResource;
use App\Models\Report;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Report::query()->with(['reporter:id,name', 'assignee:id,name'])
            ->withCount('comments');

        if ($user->hasRole('student')) {
            $query->where('reporter_id', $user->id);
        } elseif ($user->hasRole('staff')) {
            $query->where('type', 'facility');
        } elseif ($user->hasRole('counselor')) {
            $query->where('type', 'bullying');
        }

        // BARU — filter tipe, dipakai halaman profil siswa
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Load detail per-tipe TAPI tidak untuk bullying (dijaga di getTypeMeta)
        $query->with(['aspirationDetail', 'facilityDetail']);

        $reports = $query->latest()->paginate(15);

        return ReportResource::collection($reports);
    }

    public function show(Request $request, Report $report)
    {
        $this->authorize('view', $report);

        $report->loadTypeDetail();

        $report->load([
            'reporter:id,name,class_name',
            'assignee:id,name',
            'attachments',
            'statusLogs.changedBy:id,name',
        ])->loadCount('comments');

        return new ReportDetailResource($report);
    }

    public function updateStatus(Request $request, Report $report)
    {
        $this->authorize('updateStatus', $report);

        $validated = $request->validate([
            'status' => 'required|in:pending,reviewing,in_progress,resolved,rejected',
            'note' => 'nullable|string|max:500',
        ]);

        $oldStatus = $report->status;

        $report->update([
            'status' => $validated['status'],
            'resolved_at' => $validated['status'] === 'resolved' ? now() : null,
        ]);

        $report->statusLogs()->create([
            'old_status' => $oldStatus,
            'new_status' => $validated['status'],
            'changed_by' => $request->user()->id,
            'note' => $validated['note'] ?? null,
        ]);

        return response()->json([
            'message' => 'Status laporan berhasil diperbarui.',
            'report' => $report->fresh()->loadTypeDetail(),
        ]);
    }

    public function assign(Request $request, Report $report)
    {
        $this->authorize('assign', $report);

        $validated = $request->validate([
            'assigned_to' => 'required|exists:users,id',
        ]);

        $report->update($validated);

        $report->statusLogs()->create([
            'old_status' => $report->status,
            'new_status' => $report->status,
            'changed_by' => $request->user()->id,
            'note' => 'Laporan ditugaskan ke petugas.',
        ]);

        return response()->json($report->fresh()->load('assignee:id,name'));
    }

    /**
     * Mengambil daftar laporan khusus milik user yang sedang login (Siswa).
     */
    public function myReports(Request $request)
    {
        $user = $request->user();

        $reports = Report::query()
            ->where('reporter_id', $user->id)
            ->latest()
            ->paginate($request->get('per_page', 10));

        return ReportResource::collection($reports);
    }

    /**
     * Mengambil statistik ringkasan status laporan milik user yang sedang login.
     */
    public function myStats(Request $request)
    {
        $user = $request->user();

        $stats = Report::query()
            ->where('reporter_id', $user->id)
            ->selectRaw("
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
                COUNT(CASE WHEN status = 'reviewing' THEN 1 END) as reviewing,
                COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress,
                COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved,
                COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected
            ")
            ->first();

        return response()->json([
            'pending'     => (int) $stats->pending,
            'reviewing'   => (int) $stats->reviewing,
            'in_progress' => (int) $stats->in_progress,
            'resolved'    => (int) $stats->resolved,
            'rejected'    => (int) $stats->rejected,
        ]);
    }
}
