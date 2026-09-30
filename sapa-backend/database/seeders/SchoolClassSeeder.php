<?php

namespace Database\Seeders;

use App\Models\SchoolClass;
use Illuminate\Database\Seeder;

class SchoolClassSeeder extends Seeder
{
    public function run(): void
    {
        $majors = ['RPL', 'TKR', 'TSM'];
        $grades = ['X', 'XI', 'XII'];

        foreach ($grades as $grade) {
            foreach ($majors as $major) {
                for ($seq = 1; $seq <= 2; $seq++) {
                    SchoolClass::firstOrCreate([
                        'grade' => $grade,
                        'major' => $major,
                        'sequence' => $seq,
                        'academic_year' => '2025/2026',
                    ], [
                        'name' => "{$grade} {$major} {$seq}",
                    ]);
                }
            }
        }
    }
}
