<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('categories.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'description' => $this->filled('description') ? trim((string) $this->input('description')) : null,
        ]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        /** @var Category|null $category */
        $category = $this->route('category');
        $categoryId = $category instanceof Category ? $category->id : $category;
        $name = strtolower(trim((string) $this->input('name')));

        return [
            'name' => [
                'required',
                'string',
                'min:3',
                'max:100',
                Rule::unique(Category::class, 'name')
                    ->ignore($categoryId)
                    ->where(function ($query) use ($name, $categoryId) {
                        return $query->whereRaw('LOWER(name) = ?', [$name])
                            ->where('id', '!=', $categoryId);
                    }),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.min' => 'Nama kategori minimal 3 karakter.',
            'name.max' => 'Nama kategori maksimal 100 karakter.',
            'name.unique' => 'Nama kategori sudah digunakan.',
            'description.max' => 'Deskripsi kategori maksimal 500 karakter.',
        ];
    }
}
