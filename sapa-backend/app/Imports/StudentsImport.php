<?php

namespace App\Imports;

use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StudentsImport implements ToCollection, WithHeadingRow
{
    public int $successCount = 0;
    public array $errors = [];
    private int $rowNumber = 1;

    /**
     * @param Collection $rows
     */
    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $this->rowNumber++;

            $name = trim((string) ($row['nama'] ?? ''));
            $email = trim((string) ($row['email'] ?? ''));
            $nisn = trim((string) ($row['nisn'] ?? ''));
            $className = trim((string) ($row['nama_kelas'] ?? ''));
            $phone = trim((string) ($row['no_telepon'] ?? ''));
            $password = trim((string) ($row['password'] ?? ''));

            // Sla volledig lege rijen over
            if (empty($name) && empty($email) && empty($nisn)) {
                continue;
            }

            $rowErrors = [];
            if (empty($name)) {
                $rowErrors[] = 'nama kosong';
            }
            if (empty($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $rowErrors[] = 'email tidak valid';
            }
            if (empty($nisn)) {
                $rowErrors[] = 'NISN kosong';
            }

            if (! empty($email) && User::where('email', $email)->exists()) {
                $rowErrors[] = 'email sudah terdaftar';
            }
            if (! empty($nisn) && User::where('identity_number', $nisn)->exists()) {
                $rowErrors[] = 'NISN sudah terdaftar';
            }

            $class = null;
            if (! empty($className)) {
                $class = SchoolClass::where('name', $className)->first();
                if (! $class) {
                    $rowErrors[] = "kelas '{$className}' tidak ditemukan";
                }
            }

            if (! empty($rowErrors)) {
                $this->errors[] = "Baris {$this->rowNumber}: " . implode(', ', $rowErrors);
                continue;
            }

            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password ?: 'siswa123'),
                'identity_number' => $nisn,
                'class_id' => $class?->id,
                'phone' => $phone ?: null,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            $user->assignRole('student');
            $this->successCount++;
        }
    }
}
