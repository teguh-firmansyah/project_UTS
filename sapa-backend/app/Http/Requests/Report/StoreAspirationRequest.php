<?php

namespace App\Http\Requests\Report;

use Illuminate\Foundation\Http\FormRequest;

class StoreAspirationRequest extends FormRequest
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
            'category' => ['required', 'in:academic,facility_policy,school_policy,other'],
            'is_anonymous' => ['boolean'],
            'is_public' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.min' => 'Judul minimal 10 karakter, jelaskan aspirasi kamu dengan singkat.',
            'description.min' => 'Deskripsi minimal 20 karakter, ceritakan lebih detail.',
            'category.in' => 'Kategori tidak valid.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_anonymous' => $this->boolean('is_anonymous'),
            'is_public' => $this->boolean('is_public', true), // default true
        ]);
    }
}
