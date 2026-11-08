<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StockCancelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cancel_reason' => trim((string) $this->input('cancel_reason')),
        ]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'cancel_reason' => ['required', 'string', 'min:10', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cancel_reason.required' => 'Alasan pembatalan transaksi wajib diisi.',
            'cancel_reason.min' => 'Alasan pembatalan minimal 10 karakter.',
            'cancel_reason.max' => 'Alasan pembatalan maksimal 255 karakter.',
        ];
    }
}
