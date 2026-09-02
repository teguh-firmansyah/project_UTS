<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@sapa.sch.id'],
            [
                'name' => 'Admin SAPA',
                'password' => Hash::make('password'),
                'identity_number' => 'ADM001',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole('admin');

        // Staff (Tata Usaha)
        $staff = User::firstOrCreate(
            ['email' => 'staff@sapa.sch.id'],
            [
                'name' => 'Staff Tata Usaha',
                'password' => Hash::make('password'),
                'identity_number' => 'STF001',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $staff->assignRole('staff');

        // Guru BK (Counselor)
        $counselor = User::firstOrCreate(
            ['email' => 'bk@sapa.sch.id'],
            [
                'name' => 'Guru BK - Ibu Sari',
                'password' => Hash::make('password'),
                'identity_number' => 'BK001',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        $counselor->assignRole('counselor');

        // Student (beberapa contoh untuk testing)
        $students = [
            ['name' => 'Ahmad Fadillah', 'email' => 'ahmad@sapa.sch.id', 'nis' => '2024001', 'class' => 'XII RPL 1'],
            ['name' => 'Siti Nurhaliza', 'email' => 'siti@sapa.sch.id', 'nis' => '2024002', 'class' => 'XII RPL 1'],
            ['name' => 'Budi Santoso', 'email' => 'budi@sapa.sch.id', 'nis' => '2024003', 'class' => 'XI RPL 2'],
        ];

        foreach ($students as $data) {
            $student = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                    'identity_number' => $data['nis'],
                    'class_name' => $data['class'],
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
            $student->assignRole('student');
        }
    }
}
