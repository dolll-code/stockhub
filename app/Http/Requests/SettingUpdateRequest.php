<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SettingUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'company_name' => trim((string) $this->input('company_name')),
            'company_address' => $this->filled('company_address') ? trim((string) $this->input('company_address')) : null,
            'company_phone' => $this->filled('company_phone') ? trim((string) $this->input('company_phone')) : null,
            'max_backdate_days' => (int) $this->input('max_backdate_days', 7),
            'remove_logo' => $this->boolean('remove_logo'),
        ]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'min:3', 'max:150'],
            'company_address' => ['nullable', 'string', 'max:500'],
            'company_phone' => ['nullable', 'string', 'min:6', 'max:30', 'regex:/^[\d\s\+\(\)\-]+$/'],
            'max_backdate_days' => ['required', 'integer', 'min:0', 'max:30'],
            'company_logo' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png',
                'max:1024', // max 1 MB (FR-SET-02)
            ],
            'remove_logo' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'company_name.required' => 'Nama perusahaan wajib diisi.',
            'company_name.min' => 'Nama perusahaan minimal 3 karakter.',
            'company_name.max' => 'Nama perusahaan maksimal 150 karakter.',
            'company_address.max' => 'Alamat perusahaan maksimal 500 karakter.',
            'company_phone.min' => 'Nomor telepon minimal 6 karakter.',
            'company_phone.max' => 'Nomor telepon maksimal 30 karakter.',
            'company_phone.regex' => 'Format nomor telepon hanya boleh memuat angka, spasi, +, (), dan -.',
            'max_backdate_days.required' => 'Batas tanggal lampau (max backdate days) wajib diisi.',
            'max_backdate_days.integer' => 'Batas tanggal lampau harus berupa angka bulat.',
            'max_backdate_days.min' => 'Batas tanggal lampau minimal 0 hari.',
            'max_backdate_days.max' => 'Batas tanggal lampau maksimal 30 hari.',
            'company_logo.mimes' => 'Format logo perusahaan harus berupa file PNG, JPG, atau JPEG.',
            'company_logo.max' => 'Ukuran logo perusahaan maksimal 1 MB (1024 KB).',
        ];
    }
}
