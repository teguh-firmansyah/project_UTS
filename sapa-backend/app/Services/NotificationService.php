<?php

namespace App\Services;

use App\Models\Report;
use App\Models\User;

class NotificationService
{
    /**
     * Kirim notifikasi ke semua user dengan role tertentu
     * saat ada laporan baru yang relevan.
     */
    public function notifyNewReport(Report $report): void
    {
        $recipients = match ($report->type) {
            'bullying' => User::role('counselor')->where('is_active', true)->get(),
            'facility' => User::role('staff')->where('is_active', true)->get(),
            default => collect(),
        };

        $title = match ($report->type) {
            'bullying' => 'Laporan Bullying Baru',
            'facility' => 'Laporan Fasilitas Baru',
            default => 'Laporan Baru',
        };

        foreach ($recipients as $user) {
            $user->notifications()->create([
                'report_id' => $report->id,
                'title' => $title,
                'message' => "Laporan baru dengan kode {$report->report_code} membutuhkan perhatian.",
            ]);
        }
    }
}
