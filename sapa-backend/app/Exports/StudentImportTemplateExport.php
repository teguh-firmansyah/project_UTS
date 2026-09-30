<?php

namespace App\Exports;

use App\Models\SchoolClass;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class StudentImportTemplateExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            'Template' => new TemplateSheet(),
            'Daftar Kelas' => new ClassListSheet(),
        ];
    }
}
