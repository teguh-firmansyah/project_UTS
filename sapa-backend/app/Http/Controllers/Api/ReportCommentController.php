<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CommentResource;
use App\Models\Report;
use Illuminate\Http\Request;

class ReportCommentController extends Controller
{
    public function index(Request $request, Report $report)
    {
        $this->authorize('view', $report);

        $comments = $report->comments()
            ->with('user:id,name')
            ->latest()
            ->get();

        return CommentResource::collection($comments);
    }

    public function store(Request $request, Report $report)
    {
        $this->authorize('view', $report);

        $validated = $request->validate([
            'comment' => ['required', 'string', 'max:1000'],
        ]);

        $comment = $report->comments()->create([
            'user_id' => $request->user()->id,
            'comment' => $validated['comment'],
            'is_internal' => false, // siswa tidak bisa buat catatan internal
        ]);

        $comment->load('user:id,name');

        return response()->json([
            'message' => 'Tanggapan berhasil dikirim.',
            'comment' => new CommentResource($comment),
        ], 201);
    }
}