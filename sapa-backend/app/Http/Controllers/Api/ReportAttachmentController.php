<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttachmentResource;
use App\Models\Report;
use App\Models\ReportAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReportAttachmentController extends Controller
{
    public function store(Request $request, Report $report)
    {
        $user = $request->user();

        $isOwner = $user->can('update', $report);
        $isHandler = $user->can('updateStatus', $report);

        if (! $isOwner && ! $isHandler) {
            abort(403, 'Anda tidak memiliki akses untuk menambah lampiran pada laporan ini.');
        }

        $phase = $isHandler ? 'after' : 'before';

        $validated = $request->validate([
            'attachments' => ['required', 'array', 'max:3'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ]);

        $existingCount = $report->attachments()->where('phase', $phase)->count();
        if ($existingCount + count($validated['attachments']) > 3) {
            return response()->json([
                'message' => 'Maksimal 3 lampiran untuk kategori ini.',
            ], 422);
        }

        $folder = $report->type === 'bullying'
            ? 'report-attachments/bullying'
            : 'report-attachments';

        $newAttachments = [];
        $uploadedPaths = [];

        try {
            DB::beginTransaction();

            foreach ($request->file('attachments') as $file) {
                $path = $file->store($folder, 'public');
                $uploadedPaths[] = $path;

                $newAttachments[] = $report->attachments()->create([
                    'phase' => $phase,
                    'uploaded_by' => $user->id,
                    'file_path' => $path,
                    'file_type' => $file->getClientMimeType(),
                    'file_size' => round($file->getSize() / 1024, 2),
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            // Hapus file fisik yang sempat terunggah jika DB gagal
            foreach ($uploadedPaths as $path) {
                Storage::disk('public')->delete($path);
            }

            throw $e;
        }

        return response()->json([
            'message' => $phase === 'after'
                ? 'Foto hasil perbaikan berhasil ditambahkan.'
                : 'Lampiran berhasil ditambahkan.',
            'attachments' => AttachmentResource::collection($newAttachments),
        ], 201);
    }

    public function destroy(Request $request, ReportAttachment $attachment)
    {
        $report = $attachment->report;
        $user = $request->user();

        $canManage = $user->can('update', $report) || $user->can('updateStatus', $report);
        if (! $canManage) {
            abort(403, 'Anda tidak memiliki akses untuk menghapus lampiran ini.');
        }

        $filePath = $attachment->file_path;

        DB::transaction(function () use ($attachment, $filePath) {
            $attachment->delete();
            Storage::disk('public')->delete($filePath);
        });

        return response()->json(['message' => 'Lampiran berhasil dihapus.']);
    }
}