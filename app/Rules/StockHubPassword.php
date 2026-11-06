<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StockHubPassword implements ValidationRule
{
    /**
     * @param  string|null  $email  Optional email to check that password != email
     */
    public function __construct(protected ?string $email = null) {}

    /**
     * Run the validation rule per PRD §5.1:
     * - Minimum 10 characters
     * - Contains uppercase, lowercase, numbers
     * - Not identical to email (case-insensitive)
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('Kata sandi harus berupa teks.');

            return;
        }

        if (strlen($value) < 10) {
            $fail('Kata sandi minimal harus terdiri dari 10 karakter.');

            return;
        }

        if (! preg_match('/[A-Z]/', $value)) {
            $fail('Kata sandi harus mengandung minimal satu huruf besar.');

            return;
        }

        if (! preg_match('/[a-z]/', $value)) {
            $fail('Kata sandi harus mengandung minimal satu huruf kecil.');

            return;
        }

        if (! preg_match('/[0-9]/', $value)) {
            $fail('Kata sandi harus mengandung minimal satu angka.');

            return;
        }

        if ($this->email !== null && strcasecmp(trim($value), trim($this->email)) === 0) {
            $fail('Kata sandi tidak boleh sama dengan alamat email.');

            return;
        }
    }
}
