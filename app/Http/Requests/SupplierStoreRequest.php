<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SupplierStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('suppliers.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'contact_name' => $this->filled('contact_name') ? trim((string) $this->input('contact_name')) : null,
            'phone' => $this->filled('phone') ? trim((string) $this->input('phone')) : null,
            'email' => $this->filled('email') ? strtolower(trim((string) $this->input('email'))) : null,
            'address' => $this->filled('address') ? trim((string) $this->input('address')) : null,
            'notes' => $this->filled('notes') ? trim((string) $this->input('notes')) : null,
        ]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:150'],
            'contact_name' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'min:6', 'max:30', 'regex:/^[\d\s\+\(\)\-]+$/'],
            'email' => ['nullable', 'string', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama supplier wajib diisi.',
            'name.min' => 'Nama supplier minimal 3 karakter.',
            'name.max' => 'Nama supplier maksimal 150 karakter.',
            'contact_name.max' => 'Nama kontak PIC maksimal 100 karakter.',
            'phone.min' => 'Nomor telepon minimal 6 karakter.',
            'phone.max' => 'Nomor telepon maksimal 30 karakter.',
            'phone.regex' => 'Nomor telepon hanya boleh memuat angka, spasi, tanda tambah (+), tanda hubung (-), dan tanda kurung ().',
            'email.email' => 'Format email supplier tidak valid.',
            'email.max' => 'Email supplier maksimal 150 karakter.',
            'address.max' => 'Alamat supplier maksimal 1000 karakter.',
            'notes.max' => 'Catatan supplier maksimal 1000 karakter.',
        ];
    }
}
