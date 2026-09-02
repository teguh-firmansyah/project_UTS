<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Report\StoreAspirationRequest;
use App\Models\Report;
use Illuminate\Http\Request;

class AspirationController extends Controller
{
    public function index(Request $request)
    {
        $aspirations = Report::query()
            ->ofType('aspiration')
            ->whereHas('aspirationDetail', fn($q) => $q->where('is_public', true))
            ->with(['reporter:id,name', 'aspirationDetail'])
            ->withCount('comments')
            ->latest()
            ->paginate(10);

        return response()->json($aspirations);
    }

    public function store(StoreAspirationRequest $request)
    {
        $validated = $request->validated();
        $user = $request->user();

        $report = Report::create([
            'reporter_id' => $validated['is_anonymous'] ? null : $user->id,
            'type' => 'aspiration',
            'title' => $validated['title'],
            'description' => $validated['description'],
            'is_anonymous' => $validated['is_anonymous'],
            'status' => 'pending',
        ]);

        $report->aspirationDetail()->create([
            'category' => $validated['category'],
            'is_public' => $validated['is_public'],
        ]);

        $report->statusLogs()->create([
            'old_status' => null,
            'new_status' => 'pending',
            'changed_by' => $user->id,
            'note' => 'Aspirasi diajukan.',
        ]);

        return response()->json([
            'message' => 'Aspirasi berhasil diajukan.',
            'report' => $report->load('aspirationDetail'),
        ], 201);
    }

    public function upvote(Request $request, Report $report)
    {
        if ($report->type !== 'aspiration') {
            return response()->json(['message' => 'Laporan ini bukan aspirasi.'], 422);
        }

        $report->aspirationDetail()->increment('upvotes_count');

        return response()->json([
            'message' => 'Upvote berhasil.',
            'upvotes_count' => $report->aspirationDetail->fresh()->upvotes_count,
        ]);
    }
}
