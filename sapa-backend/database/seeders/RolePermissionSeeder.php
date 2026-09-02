<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Cache;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cache permission (wajib, biar tidak ada cache lama nyangkut)
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ============ PERMISSIONS ============
        $permissions = [
            // Report - umum
            'report.create',
            'report.view_own',
            'report.view_all',

            // Aspirasi
            'aspiration.view_public',
            'aspiration.comment',
            'aspiration.upvote',

            // Fasilitas
            'facility.manage',      // update status, tangani laporan fasilitas
            'facility.view_all',

            // Bullying - paling sensitif
            'bullying.handle',      // hanya counselor: lihat detail & tangani
            'bullying.view_metadata', // admin: hanya lihat jumlah/status, bukan isi

            // Admin / manajemen
            'user.manage',
            'report.assign',
            'report.export',
            'dashboard.analytics',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // ============ ROLES ============

        // Student — role paling dasar
        $student = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
        $student->syncPermissions([
            'report.create',
            'report.view_own',
            'aspiration.view_public',
            'aspiration.comment',
            'aspiration.upvote',
        ]);

        // Staff — menangani fasilitas
        $staff = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        $staff->syncPermissions([
            'facility.manage',
            'facility.view_all',
            'aspiration.view_public',
            'report.assign',
        ]);

        // Counselor (Guru BK) — HANYA role ini yang boleh pegang bullying.handle
        $counselor = Role::firstOrCreate(['name' => 'counselor', 'guard_name' => 'web']);
        $counselor->syncPermissions([
            'bullying.handle',
            'report.view_own',
        ]);

        // Admin — kelola sistem, TAPI tidak dapat bullying.handle
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions([
            'report.view_all',
            'facility.view_all',
            'bullying.view_metadata', // hanya metadata, bukan bullying.handle
            'aspiration.view_public',
            'user.manage',
            'report.assign',
            'report.export',
            'dashboard.analytics',
        ]);
    }
}
