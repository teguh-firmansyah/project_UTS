<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttachmentResource;
use App\Models\Report;
use App\Models\ReportAttachment;
use Illuminate\Http\Request;

class ReportAttachmentController extends Controller
{
    /**
     * Tambah lampiran ke laporan yang sudah ada.
     * Dipakai untuk kasus siswa ingin menyusulkan bukti tambahan
     * setelah laporan awal terkirim (bukan saat submit pertama kali —
     * itu sudah ditangani masing-masing StoreXxxRequest).
     */
    public function store(Request $request, Report $report)
    {
        // Hanya pemilik laporan yang belum diproses boleh menambah lampiran,
        // pakai aturan yang sama dengan Policy::update()
        $this->authorize('update', $report);

        $validated = $request->validate([
            'attachments' => ['required', 'array', 'max:3'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ]);

        // Cegah total lampiran melebihi batas 3 per laporan
        $existingCount = $report->attachments()->count();
        if ($existingCount + count($validated['attachments']) > 3) {
            return response()->json([
                'message' => "Laporan ini sudah memiliki {$existingCount} lampiran. Maksimal 3 lampiran per laporan.",
            ], 422);
        }

        // Folder berbeda untuk bullying, konsisten dengan BullyingReportController::store()
        $folder = $report->type === 'bullying'
            ? 'report-attachments/bullying'
            : 'report-attachments';

        $newAttachments = [];
        foreach ($request->file('attachments') as $file) {
            $path = $file->store($folder, 'public');
            $newAttachments[] = $report->attachments()->create([
                'file_path' => $path,
                'file_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize() / 1024,
            ]);
        }

        return response()->json([
            'message' => 'Lampiran berhasil ditambahkan.',
            'attachments' => AttachmentResource::collection($newAttachments),
        ], 201);
    }

    /**
     * Hapus satu lampiran.
     * Hanya pemilik laporan (dan hanya sebelum diproses) yang boleh hapus —
     * sama seperti aturan update laporan itu sendiri.
     */
    public function destroy(Request $request, ReportAttachment $attachment)
    {
        $report = $attachment->report;

        $this->authorize('update', $report);

        $filePath = $attachment->file_path;
        $attachment->delete();

        \Illuminate\Support\Facades\Storage::disk('public')->delete($filePath);

        return response()->json([
            'message' => 'Lampiran berhasil dihapus.',
        ]);
    }
}
