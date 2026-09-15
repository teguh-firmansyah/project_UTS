<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BullyingQueueResource extends JsonResource
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

            // Hanya tampil kalau tidak anonim — tetap dijaga di sini
            'reporter' => $this->when(
                ! $this->is_anonymous && $this->relationLoaded('reporter') && $this->reporter,
                fn() => [
                    'name' => $this->reporter->name,
                    'class_name' => $this->reporter->class_name,
                ]
            ),

            'detail' => $this->whenLoaded('bullyingDetail', fn() => [
                'reporter_relation' => $this->bullyingDetail->reporter_relation,
                'incident_date' => $this->bullyingDetail->incident_date?->toDateString(),
                'handled_by' => $this->bullyingDetail->counselor?->name,
                'handling_notes' => $this->bullyingDetail->handling_notes,
            ]),

            'created_at' => $this->created_at?->toIso8601String(),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
        ];
    }
}
