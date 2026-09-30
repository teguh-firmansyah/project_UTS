<?php

namespace App\Services;

use App\Models\Report;
use App\Models\User;

class NotificationService
{
    public function notifyAdminsNewReport(Report $report): void
    {
        $admins = User::role('admin')->where('is_active', true)->get();

        foreach ($admins as $admin) {
            $admin->notifications()->create([
                'report_id' => $report->id,
                'title' => 'Laporan Baru Perlu Ditugaskan',
                'message' => "Laporan {$report->report_code} masuk dan menunggu penugasan petugas.",
            ]);
        }
    }
}
