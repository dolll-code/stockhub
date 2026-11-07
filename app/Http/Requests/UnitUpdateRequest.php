<?php

namespace App\Http\Requests;

use App\Models\Unit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UnitUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('units.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'symbol' => trim((string) $this->input('symbol')),
            'description' => $this->filled('description') ? trim((string) $this->input('description')) : null,
            'allow_decimal' => $this->boolean('allow_decimal'),
        ]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        /** @var Unit|null $unit */
        $unit = $this->route('unit');
        $unitId = $unit instanceof Unit ? $unit->id : $unit;

        return [
            'name' => ['required', 'string', 'min:2', 'max:50', Rule::unique(Unit::class, 'name')->ignore($unitId)],
            'symbol' => ['required', 'string', 'min:1', 'max:10', Rule::unique(Unit::class, 'symbol')->ignore($unitId)],
            'allow_decimal' => ['required', 'boolean'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama satuan wajib diisi.',
            'name.min' => 'Nama satuan minimal 2 karakter.',
            'name.max' => 'Nama satuan maksimal 50 karakter.',
            'name.unique' => 'Nama satuan sudah digunakan.',
            'symbol.required' => 'Simbol satuan wajib diisi.',
            'symbol.min' => 'Simbol satuan minimal 1 karakter.',
            'symbol.max' => 'Simbol satuan maksimal 10 karakter.',
            'symbol.unique' => 'Simbol satuan sudah digunakan.',
            'allow_decimal.required' => 'Tipe kuantitas (bulat/desimal) wajib ditentukan.',
            'description.max' => 'Deskripsi satuan maksimal 255 karakter.',
        ];
    }
}
