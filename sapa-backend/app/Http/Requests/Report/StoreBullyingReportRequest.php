<?php

namespace App\Http\Requests\Report;

use Illuminate\Foundation\Http\FormRequest;

class StoreBullyingReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermissionTo('report.create');
    }

    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'min:20', 'max:3000'],
            'reporter_relation' => ['required', 'in:victim,witness'],
            'incident_date' => ['nullable', 'date', 'before_or_equal:today'],
            'is_anonymous' => ['boolean'],

            'attachments' => ['nullable', 'array', 'max:3'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'description.min' => 'Ceritakan kejadiannya minimal 20 karakter agar bisa ditindaklanjuti dengan baik.',
            'reporter_relation.required' => 'Pilih apakah kamu korban langsung atau saksi.',
            'incident_date.before_or_equal' => 'Tanggal kejadian tidak boleh di masa depan.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            // Anonim TIDAK boleh default false untuk bullying —
            // beri opsi eksplisit tapi jangan dipaksa true otomatis,
            // biarkan siswa yang memilih dengan sadar.
            'is_anonymous' => $this->boolean('is_anonymous'),
        ]);
    }

    /**
     * title otomatis di-generate di controller/service,
     * TIDAK diminta dari user — supaya tidak ada judul yang
     * secara tidak sengaja jadi identifiable atau tidak pantas.
     */
}
