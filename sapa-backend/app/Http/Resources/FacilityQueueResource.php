<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FacilityQueueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'report_code' => $this->report_code,
            'title' => $this->title,
            'status' => $this->status,
            'priority' => $this->priority,
            'is_anonymous' => $this->is_anonymous,

            // Fasilitas boleh tampilkan pelapor di list (beda dari bullying)
            'reporter' => $this->when(
                ! $this->is_anonymous && $this->relationLoaded('reporter'),
                fn() => $this->reporter ? [
                    'id' => $this->reporter->id,
                    'name' => $this->reporter->name,
                ] : null
            ),

            'assignee' => $this->when(
                $this->relationLoaded('assignee') && $this->assignee,
                fn() => ['id' => $this->assignee->id, 'name' => $this->assignee->name]
            ),

            'detail' => $this->whenLoaded('facilityDetail', fn() => [
                'location' => $this->facilityDetail->location,
                'category' => $this->facilityDetail->category,
                'damage_level' => $this->facilityDetail->damage_level,
            ]),

            'attachments_count' => $this->whenCounted('attachments'),

            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
