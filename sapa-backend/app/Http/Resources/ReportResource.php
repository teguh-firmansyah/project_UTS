<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'report_code' => $this->report_code,
            'type' => $this->type,
            'title' => $this->title,
            // Deskripsi dipotong di list — detail penuh hanya di endpoint show()
            'description_excerpt' => str($this->description)->limit(120)->toString(),
            'status' => $this->status,
            'priority' => $this->priority,
            'is_anonymous' => $this->is_anonymous,

            // Reporter HANYA ditampilkan kalau tidak anonim
            'reporter' => $this->when(
                ! $this->is_anonymous && $this->relationLoaded('reporter'),
                fn() => $this->reporter ? [
                    'id' => $this->reporter->id,
                    'name' => $this->reporter->name,
                ] : null
            ),

            'assignee' => $this->when(
                $this->relationLoaded('assignee') && $this->assignee,
                fn() => [
                    'id' => $this->assignee->id,
                    'name' => $this->assignee->name,
                ]
            ),

            // Metadata ringan per tipe, TANPA field sensitif (mis. handling_notes bullying)
            'type_meta' => $this->getTypeMeta(),

            'comments_count' => $this->when(
                $this->comments_count !== null,
                $this->comments_count
            ),

            'created_at' => $this->created_at?->toIso8601String(),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
        ];
    }

    /**
     * Metadata ringan per tipe — sengaja tidak expose field sensitif
     * seperti handling_notes atau reporter_relation bullying di LIST.
     */
    protected function getTypeMeta(): ?array
    {
        return match ($this->type) {
            'aspiration' => $this->relationLoaded('aspirationDetail') && $this->aspirationDetail
                ? [
                    'category' => $this->aspirationDetail->category,
                    'upvotes_count' => $this->aspirationDetail->upvotes_count,
                ]
                : null,

            'facility' => $this->relationLoaded('facilityDetail') && $this->facilityDetail
                ? [
                    'location' => $this->facilityDetail->location,
                    'category' => $this->facilityDetail->category,
                ]
                : null,

            // Bullying: TIDAK ADA meta sama sekali di list, bahkan untuk counselor —
            // detail hanya dibuka lewat endpoint show() yang sudah di-authorize
            'bullying' => null,

            default => null,
        };
    }
}
