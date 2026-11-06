<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Rules\StockHubPassword;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('users.manage') ?? false;
    }

    /**
     * Prepare inputs for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim((string) $this->input('email'))),
            'name' => trim((string) $this->input('name')),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $email = (string) $this->input('email');

        return [
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'email' => [
                'required',
                'string',
                'email',
                'max:150',
                Rule::unique(User::class, 'email'),
            ],
            'role' => [
                'required',
                'string',
                Rule::in(['super_admin', 'admin_gudang', 'staff_gudang', 'manajer']),
            ],
            'password' => [
                'required',
                'string',
                new StockHubPassword($email),
            ],
        ];
    }
}
