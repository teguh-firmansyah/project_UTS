<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Report\StoreFacilityReportRequest;
use App\Http\Resources\FacilityQueueResource;
use App\Models\Report;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class FacilityReportController extends Controller
{
    public function index(Request $request)
    {
        $reports = Report::query()
            ->ofType('facility')
            ->with(['reporter:id,name', 'facilityDetail', 'assignee:id,name'])
            ->withCount('attachments')
            ->latest()
            ->paginate(15);

        return FacilityQueueResource::collection($reports);
    }

    /**
     * Antrian utama staff — dengan filter status, kategori, dan tingkat kerusakan.
     */
    public function queue(Request $request)
    {
        $this->authorize('viewAny', Report::class);

        $query = Report::query()
            ->ofType('facility')
            ->where('assigned_to', $request->user()->id)
            ->with(['reporter:id,name', 'facilityDetail', 'assignee:id,name', 'statusLogs' => function ($q) {
                $q->latest()->limit(1);
            }])
            ->withCount('attachments');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->whereIn('status', ['pending', 'reviewing', 'in_progress']);
        }

        if ($request->filled('category')) {
            $query->whereHas('facilityDetail', fn($q) => $q->where('category', $request->category));
        }
        if ($request->filled('damage_level')) {
            $query->whereHas('facilityDetail', fn($q) => $q->where('damage_level', $request->damage_level));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $reports = $query->orderByRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low')")
            ->latest()
            ->paginate(15);

        return FacilityQueueResource::collection($reports);
    }

    /**
     * Statistik untuk dashboard staff — pola identik dengan BullyingReportController::stats()
     */
    public function stats(Request $request)
    {
        $this->authorize('viewAny', Report::class);

        $stats = Report::ofType('facility')
            ->where('assigned_to', $request->user()->id)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $byCategory = Report::ofType('facility')
            ->where('assigned_to', $request->user()->id)
            ->join('facility_report_details', 'reports.id', '=', 'facility_report_details.report_id')
            ->selectRaw('facility_report_details.category, count(*) as total')
            ->groupBy('facility_report_details.category')
            ->pluck('total', 'category');

        return response()->json([
            'pending' => $stats['pending'] ?? 0,
            'reviewing' => $stats['reviewing'] ?? 0,
            'in_progress' => $stats['in_progress'] ?? 0,
            'resolved' => $stats['resolved'] ?? 0,
            'rejected' => $stats['rejected'] ?? 0,
            'total' => $stats->sum(),
            'by_category' => $byCategory,
        ]);
    }

    public function store(StoreFacilityReportRequest $request, NotificationService $notificationService)
    {
        $validated = $request->validated();
        $user = $request->user();

        $report = Report::create([
            'reporter_id' => $user->id,
            'type' => 'facility',
            'title' => $validated['title'],
            'description' => $validated['description'],
            'is_anonymous' => $validated['is_anonymous'],
            'status' => 'pending',
        ]);

        $report->facilityDetail()->create([
            'location' => $validated['location'],
            'category' => $validated['category'],
            'damage_level' => $validated['damage_level'] ?? null,
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('report-attachments', 'public');
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
            'note' => 'Laporan fasilitas diajukan.',
        ]);

        $notificationService->notifyAdminsNewReport($report);

        return response()->json([
            'message' => 'Laporan fasilitas berhasil dikirim.',
            'report' => $report->load(['facilityDetail', 'attachments']),
        ], 201);
    }

    public function availableStaff(Request $request)
    {
        $this->authorize('assign', Report::class);

        $staff = \App\Models\User::role('staff')
            ->where('is_active', true)
            ->select('id', 'name')
            ->get();

        return response()->json($staff);
    }
}
