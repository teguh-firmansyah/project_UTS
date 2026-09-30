<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StaffExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    /**
     * @param array $roles daftar role yang di-export: ['staff'], ['counselor'], atau ['staff', 'counselor']
     */
    public function __construct(protected array $roles) {}

    /**
     * @return Collection
     */
    public function collection(): Collection
    {
        return User::role($this->roles)->orderBy('name')->get()->load('roles');
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return ['Nama', 'Email', 'NIP', 'No. Telepon', 'Peran', 'Status', 'Tanggal Bergabung'];
    }

    /**
     * @param mixed $user
     * @return array
     */
    public function map($user): array
    {
        $roleLabel = match ($user->roles->first()?->name) {
            'staff' => 'Guru Sarpras',
            'counselor' => 'Guru BK',
            default => '-',
        };

        return [
            $user->name,
            $user->email,
            $user->identity_number,
            $user->phone ?? '',
            $roleLabel,
            $user->is_active ? 'Aktif' : 'Nonaktif',
            $user->created_at ? $user->created_at->format('d-m-Y') : '-',
        ];
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}