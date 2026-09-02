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
            'description' => $this->description, // full, karena sudah lolos authorize
            'status' => $this->status,
            'priority' => $this->priority,
            'is_anonymous' => $this->is_anonymous,

            'reporter' => $this->when(
                ! $this->is_anonymous && $this->relationLoaded('reporter') && $this->reporter,
                fn() => [
                    'id' => $this->reporter->id,
                    'name' => $this->reporter->name,
                    'class_name' => $this->reporter->class_name,
                ]
            ),

            'assignee' => $this->when(
                $this->relationLoaded('assignee') && $this->assignee,
                fn() => ['id' => $this->assignee->id, 'name' => $this->assignee->name]
            ),

            // Detail per tipe — bullying DIJAGA LAGI DI SINI sebagai lapis terakhir,
            // bukan hanya mengandalkan authorize() di controller
            'detail' => $this->getTypeDetail($user),

            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            'status_logs' => StatusLogResource::collection($this->whenLoaded('statusLogs')),
            'comments_count' => $this->when($this->comments_count !== null, $this->comments_count),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
        ];
    }

    /**
     * Lapis pertahanan TERAKHIR untuk data bullying — bahkan kalau suatu saat
     * ada bug di Policy/middleware yang lolos, resource ini tetap menyaring
     * berdasarkan permission user secara langsung.
     */
    protected function getTypeDetail($user): ?array
    {
        return match ($this->type) {
            'aspiration' => $this->relationLoaded('aspirationDetail') && $this->aspirationDetail
                ? [
                    'category' => $this->aspirationDetail->category,
                    'upvotes_count' => $this->aspirationDetail->upvotes_count,
                    'is_public' => $this->aspirationDetail->is_public,
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
        if (! $this->relationLoaded('bullyingDetail') || ! $this->bullyingDetail) {
            return null;
        }

        // Guard eksplisit di layer Resource — pertahanan berlapis, bukan mengulang Policy
        $isCounselor = $user->hasPermissionTo('bullying.handle');
        $isOwner = ! $this->is_anonymous && $this->reporter_id === $user->id;

        if (! $isCounselor && ! $isOwner) {
            return null; // fail-safe: kalau ragu, jangan tampilkan
        }

        $detail = [
            'reporter_relation' => $this->bullyingDetail->reporter_relation,
            'incident_date' => $this->bullyingDetail->incident_date?->toDateString(),
        ];

        // handling_notes HANYA untuk counselor, siswa pemilik tidak perlu lihat catatan internal BK
        if ($isCounselor) {
            $detail['handling_notes'] = $this->bullyingDetail->handling_notes;
            $detail['handled_by'] = $this->bullyingDetail->counselor?->name;
        }

        return $detail;
    }
}
