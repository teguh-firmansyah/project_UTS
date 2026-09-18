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
            ->with(['reporter:id,name', 'facilityDetail', 'assignee:id,name'])
            ->withCount('attachments');

        // Filter status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->whereIn('status', ['pending', 'reviewing', 'in_progress']);
        }

        // Filter kategori kerusakan (electricity, furniture, sanitation, building, other)
        if ($request->filled('category')) {
            $query->whereHas('facilityDetail', function ($q) use ($request) {
                $q->where('category', $request->category);
            });
        }

        // Filter tingkat kerusakan (minor, moderate, severe)
        if ($request->filled('damage_level')) {
            $query->whereHas('facilityDetail', function ($q) use ($request) {
                $q->where('damage_level', $request->damage_level);
            });
        }

        // Filter rentang tanggal
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
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        // Tambahan khusus fasilitas: breakdown per kategori kerusakan
        $byCategory = Report::ofType('facility')
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

        $notificationService->notifyNewReport($report);

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
