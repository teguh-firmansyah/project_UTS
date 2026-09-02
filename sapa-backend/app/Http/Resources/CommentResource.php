<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();

        // is_internal comment (catatan staff) hanya boleh dilihat staff/admin/counselor
        if ($this->is_internal && $user->hasRole('student')) {
            return [];
        }

        return [
            'id' => $this->id,
            'comment' => $this->comment,
            'is_internal' => $this->is_internal,
            'author' => $this->whenLoaded('user', fn() => $this->user ? [
                'name' => $this->user->name,
            ] : ['name' => 'Anonim']),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
