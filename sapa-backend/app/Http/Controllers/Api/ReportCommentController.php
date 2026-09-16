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
            ->with(['user:id,name'])
            ->with('report') // dibutuhkan CommentResource untuk cek anonimitas
            ->oldest() // urut lama ke baru, cocok untuk tampilan chat
            ->get();

        return CommentResource::collection($comments)
            ->collection
            ->filter() // buang array kosong dari is_internal yang disembunyikan
            ->values();
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
            'is_internal' => false,
        ]);

        $comment->load(['user:id,name', 'report']);

        return response()->json([
            'message' => 'Pesan berhasil dikirim.',
            'comment' => new CommentResource($comment),
        ], 201);
    }
}
