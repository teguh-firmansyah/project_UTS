<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class StudentsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function __construct(
        protected ?int $classId = null,
        protected ?string $academicYear = null,
        protected ?string $grade = null,
    ) {}

    /**
     * @return Collection
     */
    public function collection(): Collection
    {
        $query = User::role('student')->with('schoolClass');

        if ($this->classId) {
            $query->where('class_id', $this->classId);
        }

        if ($this->academicYear || $this->grade) {
            $query->whereHas('schoolClass', function ($q) {
                if ($this->academicYear) {
                    $q->where('academic_year', $this->academicYear);
                }
                if ($this->grade) {
                    $q->where('grade', $this->grade);
                }
            });
        }

        return $query->orderBy('name')->get();
    }

    public function headings(): array
    {
        return ['Nama', 'Email', 'NISN', 'Kelas', 'Tahun Ajaran', 'No. Telepon', 'Status'];
    }

    public function map($student): array
    {
        return [
            $student->name,
            $student->email,
            $student->identity_number,
            $student->schoolClass?->name ?? '',
            $student->schoolClass?->academic_year ?? '',
            $student->phone ?? '',
            $student->is_active ? 'Aktif' : 'Nonaktif',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
        ];
    }
}
