<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'report_code' => $this->report_code,
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'is_anonymous' => (bool) $this->is_anonymous,

            // Pelapor dimuat HANYA JIKA TIDAK ANONIM
            'reporter' => $this->when(
                ! $this->is_anonymous && $this->relationLoaded('reporter') && $this->reporter,
                fn() => [
                    'id' => $this->reporter->id,
                    'name' => $this->reporter->name,
                    'class_name' => $this->reporter->class_name ?? '-',
                ]
            ),

            'assignee' => $this->when(
                $this->relationLoaded('assignee') && $this->assignee,
                fn() => [
                    'id' => $this->assignee->id,
                    'name' => $this->assignee->name
                ]
            ),

            'detail' => $this->getTypeDetail($user),

            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            'status_logs' => StatusLogResource::collection($this->whenLoaded('statusLogs')),
            'comments_count' => $this->when($this->comments_count !== null, (int) $this->comments_count),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
        ];
    }

    protected function getTypeDetail($user): ?array
    {
        return match ($this->type) {
            'aspiration' => $this->relationLoaded('aspirationDetail') && $this->aspirationDetail
                ? [
                    'category' => $this->aspirationDetail->category,
                    'upvotes_count' => $this->aspirationDetail->upvotes_count ?? 0,
                    'is_public' => (bool) ($this->aspirationDetail->is_public ?? true),
                ]
                : null,

            'facility' => $this->relationLoaded('facilityDetail') && $this->facilityDetail
                ? [
                    'location' => $this->facilityDetail->location,
                    'category' => $this->facilityDetail->category,
                    'damage_level' => $this->facilityDetail->damage_level,
                ]
                : null,

            'bullying' => $this->getBullyingDetailIfAuthorized($user),

            default => null,
        };
    }

    protected function getBullyingDetailIfAuthorized($user): ?array
    {
        if (! $user || ! $this->relationLoaded('bullyingDetail') || ! $this->bullyingDetail) {
            return null;
        }

        // PERBAIKAN 1: Pemilik laporan (reporter_id) SELALU dianggap owner, walau laporannya anonim
        $isCounselor = $user->hasPermissionTo('bullying.handle');
        $isOwner = $this->reporter_id === $user->id;

        if (! $isCounselor && ! $isOwner) {
            return null;
        }

        // PERBAIKAN 2: Proteksi jika incident_date bertipe string / Carbon / null
        $incidentDate = $this->bullyingDetail->incident_date;
        if ($incidentDate instanceof \DateTimeInterface) {
            $formattedDate = $incidentDate->format('Y-m-d');
        } else {
            $formattedDate = $incidentDate ? (string) $incidentDate : null;
        }

        $detail = [
            'reporter_relation' => $this->bullyingDetail->reporter_relation,
            'incident_date' => $formattedDate,
        ];

        if ($isCounselor) {
            $detail['handling_notes'] = $this->bullyingDetail->handling_notes;
            $detail['handled_by'] = $this->bullyingDetail->counselor?->name;
        }

        return $detail;
    }
}
