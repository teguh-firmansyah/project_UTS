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
            'detail' => $this->whenLoaded('bullyingDetail', fn() => [
                'reporter_relation' => $this->bullyingDetail->reporter_relation,
                'incident_date' => $this->bullyingDetail->incident_date?->toDateString(),
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
