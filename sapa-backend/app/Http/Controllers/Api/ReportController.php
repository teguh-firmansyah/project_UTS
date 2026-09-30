<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReportDetailResource;
use App\Http\Resources\ReportResource;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Report::query()
            ->with(['reporter:id,name', 'assignee:id,name'])
            ->withCount('comments');

        // Filter berdasarkan Role
        if ($user->hasRole('student')) {
            $query->where('reporter_id', $user->id);
        } elseif ($user->hasRole('staff')) {
            $query->where('type', 'facility');
        } elseif ($user->hasRole('counselor')) {
            $query->where('type', 'bullying');
        }

        // Filter Tipe Laporan
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $query->with(['aspirationDetail', 'facilityDetail']);

        $reports = $query->latest()->paginate($request->get('per_page', 15));

        return ReportResource::collection($reports);
    }

    public function show(Request $request, Report $report)
    {
        $this->authorize('view', $report);

        $report->loadTypeDetail();

        $report->load([
            'reporter:id,name,class_id',
            'reporter.schoolClass:id,name,academic_year',
            'assignee:id,name',
            'attachments.uploader:id,name',
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

        DB::transaction(function () use ($report, $validated, $request) {
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

            if ($report->reporter_id) {
                $report->reporter->notifications()->create([
                    'report_id' => $report->id,
                    'title' => 'Status Laporan Diperbarui',
                    'message' => "Laporan {$report->report_code} kini berstatus: " . ucfirst(str_replace('_', ' ', $validated['status'])),
                ]);
            }
        });

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

        DB::transaction(function () use ($report, $validated, $request) {
            $oldStatus = $report->status;

            $report->update([
                'assigned_to' => $validated['assigned_to'],
            ]);

            $report->statusLogs()->create([
                'old_status' => $oldStatus,
                'new_status' => $report->status,
                'changed_by' => $request->user()->id,
                'note' => 'Laporan ditugaskan ke petugas.',
            ]);

            $assignee = User::find($validated['assigned_to']);
            $assignee?->notifications()->create([
                'report_id' => $report->id,
                'title' => 'Laporan Baru Ditugaskan',
                'message' => "Laporan {$report->report_code} telah ditugaskan kepada Anda dan membutuhkan penanganan.",
            ]);
        });

        return response()->json([
            'message' => 'Laporan berhasil ditugaskan.',
            'report' => $report->fresh()->load('assignee:id,name'),
        ]);
    }

    public function myReports(Request $request)
    {
        $user = $request->user();

        $reports = Report::query()
            ->where('reporter_id', $user->id)
            ->with(['reporter:id,name', 'assignee:id,name', 'aspirationDetail', 'facilityDetail'])
            ->withCount('comments')
            ->latest()
            ->paginate($request->get('per_page', 10));

        return ReportResource::collection($reports);
    }

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
            'pending'     => (int) ($stats->pending ?? 0),
            'reviewing'   => (int) ($stats->reviewing ?? 0),
            'in_progress' => (int) ($stats->in_progress ?? 0),
            'resolved'    => (int) ($stats->resolved ?? 0),
            'rejected'    => (int) ($stats->rejected ?? 0),
        ]);
    }

    public function destroy(Request $request, Report $report)
    {
        $this->authorize('delete', $report);

        DB::transaction(function () use ($report) {
            foreach ($report->attachments as $attachment) {
                Storage::disk('public')->delete($attachment->file_path);
            }

            $report->forceDelete();
        });

        return response()->json([
            'message' => 'Laporan beserta seluruh data terkait berhasil dihapus.',
        ]);
    }
}
