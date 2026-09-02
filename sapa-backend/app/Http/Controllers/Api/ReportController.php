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
}
