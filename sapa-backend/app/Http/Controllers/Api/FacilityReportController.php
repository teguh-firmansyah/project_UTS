<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Report\StoreFacilityReportRequest;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FacilityReportController extends Controller
{
    public function index(Request $request)
    {
        $reports = Report::query()
            ->ofType('facility')
            ->with(['reporter:id,name', 'facilityDetail', 'attachments'])
            ->latest()
            ->paginate(15);

        return response()->json($reports);
    }

    public function queue(Request $request)
    {
        $reports = Report::query()
            ->ofType('facility')
            ->whereIn('status', ['pending', 'reviewing', 'in_progress'])
            ->with(['reporter:id,name', 'facilityDetail'])
            ->orderByRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low')")
            ->latest()
            ->paginate(15);

        return response()->json($reports);
    }

    public function store(StoreFacilityReportRequest $request)
    {
        $validated = $request->validated();
        $user = $request->user();

        $report = Report::create([
            'reporter_id' => $validated['is_anonymous'] ? null : $user->id,
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
                    'file_size' => $file->getSize() / 1024, // KB
                ]);
            }
        }

        $report->statusLogs()->create([
            'old_status' => null,
            'new_status' => 'pending',
            'changed_by' => $user->id,
            'note' => 'Laporan fasilitas diajukan.',
        ]);

        return response()->json([
            'message' => 'Laporan fasilitas berhasil dikirim.',
            'report' => $report->load(['facilityDetail', 'attachments']),
        ], 201);
    }
}
