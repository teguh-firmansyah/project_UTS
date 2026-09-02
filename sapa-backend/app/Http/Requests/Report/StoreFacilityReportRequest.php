<?php

namespace App\Http\Requests\Report;

use Illuminate\Foundation\Http\FormRequest;

class StoreFacilityReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermissionTo('report.create');
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:10', 'max:200'],
            'description' => ['required', 'string', 'min:20', 'max:3000'],
            'location' => ['required', 'string', 'max:150'],
            'category' => ['required', 'in:electricity,furniture,sanitation,building,other'],
            'damage_level' => ['nullable', 'in:minor,moderate,severe'],
            'is_anonymous' => ['boolean'],

            // Lampiran foto — opsional
            'attachments' => ['nullable', 'array', 'max:3'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'location.required' => 'Lokasi kerusakan wajib diisi, mis. "Toilet Lantai 2 Gedung B".',
            'attachments.max' => 'Maksimal 3 file lampiran.',
            'attachments.*.mimes' => 'File harus berformat jpg, png, atau pdf.',
            'attachments.*.max' => 'Ukuran file maksimal 2MB.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_anonymous' => $this->boolean('is_anonymous'),
        ]);
    }
}
