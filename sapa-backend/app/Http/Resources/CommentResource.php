<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();

        if ($this->is_internal && $user->hasRole('student')) {
            return [];
        }

        return [
            'id' => $this->id,
            'comment' => $this->comment,
            'is_internal' => $this->is_internal,
            'author' => $this->resolveAuthorName(),
            'is_mine' => $this->user_id === $user->id,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * Sembunyikan nama pengirim kalau laporan induknya anonim DAN
     * pengirim komentar adalah si pelapor itu sendiri — supaya identitas
     * tidak bocor lewat chat meskipun laporan sudah disembunyikan.
     */
    protected function resolveAuthorName(): array
    {
        if (! $this->relationLoaded('user') || ! $this->user) {
            return ['name' => 'Anonim'];
        }

        $report = $this->relationLoaded('report') ? $this->report : $this->report()->first();

        $isAnonymousReporter = $report
            && $report->is_anonymous
            && $this->user_id === $report->reporter_id;

        if ($isAnonymousReporter) {
            return ['name' => 'Pelapor (Anonim)'];
        }

        if ($this->user->hasRole('counselor')) {
            return ['name' => $this->user->name, 'role' => 'counselor'];
        }

        return ['name' => $this->user->name];
    }
}
