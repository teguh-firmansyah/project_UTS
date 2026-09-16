<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;

class ReportPolicy
{
    /**
     * Siapa saja yang boleh melihat DAFTAR laporan (list),
     * bukan detail — controller tetap filter query sesuai role.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            'report.view_own',
            'report.view_all',
            'facility.view_all',
        ]);
    }

    public function view(User $user, Report $report): bool
{
    // Kasus khusus: laporan bullying
    if ($report->type === 'bullying') {
        return $this->canAccessBullyingDetail($user, $report);
    }

    // PERBAIKAN: Pemilik laporan SELALU boleh lihat laporannya sendiri (termasuk jika anonim)
    if ($report->reporter_id === $user->id) {
        return true;
    }

    // Aspirasi publik boleh dilihat semua siswa
    if ($report->type === 'aspiration') {
        return $user->hasPermissionTo('aspiration.view_public');
    }

    // Fasilitas: staff/admin dengan permission view_all
    if ($report->type === 'facility') {
        return $user->hasPermissionTo('facility.view_all');
    }

    return false;
}

    public function canAccessBullyingDetail(User $user, Report $report): bool
{
    // Hanya counselor dengan permission bullying.handle
    if ($user->hasPermissionTo('bullying.handle')) {
        return true;
    }

    // PERBAIKAN: Pelapor sendiri SELALU boleh lihat status laporannya SENDIRI
    if ($report->reporter_id === $user->id) {
        return true;
    }

    // Admin biasa TIDAK termasuk di sini
    return false;
}

    /**
     * Semua yang punya permission report.create boleh membuat laporan.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('report.create');
    }

    /**
     * Update laporan (misal edit sebelum diproses) — hanya pemilik,
     * dan hanya selama status masih 'pending'.
     */
    public function update(User $user, Report $report): bool
    {
        if ($report->status !== 'pending') {
            return false;
        }

        return ! $report->is_anonymous && $report->reporter_id === $user->id;
    }

    /**
     * Update STATUS laporan (staff/BK/admin) — beda dari update biasa.
     */
    public function updateStatus(User $user, Report $report): bool
    {
        return match ($report->type) {
            'facility' => $user->hasPermissionTo('facility.manage'),
            'bullying' => $user->hasPermissionTo('bullying.handle'),
            'aspiration' => $user->hasPermissionTo('report.assign'),
            default => false,
        };
    }

    /**
     * Assign laporan ke staff/counselor tertentu.
     */
    public function assign(User $user, Report $report): bool
    {
        return $user->hasPermissionTo('report.assign');
    }

    /**
     * Hapus laporan (soft delete) — hanya admin, dan tetap tercatat di log.
     */
    public function delete(User $user, Report $report): bool
    {
        return $user->hasPermissionTo('user.manage'); // pakai permission admin-level
    }

    /**
     * Export data laporan.
     */
    public function export(User $user): bool
    {
        return $user->hasPermissionTo('report.export');
    }

    /**
     * Lihat dashboard analitik.
     */
    public function viewAnalytics(User $user): bool
    {
        return $user->hasPermissionTo('dashboard.analytics');
    }
}
