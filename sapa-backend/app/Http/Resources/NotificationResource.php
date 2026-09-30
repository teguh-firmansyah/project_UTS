<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'message' => $this->message,
            'is_read' => $this->is_read,
            'report_id' => $this->report_id,
            'report_type' => $this->whenLoaded('report', fn() => $this->report?->type),
            'report_code' => $this->whenLoaded('report', fn() => $this->report?->report_code),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
