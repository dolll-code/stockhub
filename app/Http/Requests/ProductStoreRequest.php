<?php

namespace App\Http\Requests;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProductStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('products.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $code = $this->filled('code') ? strtoupper(trim((string) $this->input('code'))) : null;

        $this->merge([
            'code' => $code,
            'name' => trim((string) $this->input('name')),
            'purchase_price' => $this->filled('purchase_price') ? $this->input('purchase_price') : 0,
            'min_stock' => $this->filled('min_stock') ? $this->input('min_stock') : 0,
            'opening_stock' => $this->filled('opening_stock') ? $this->input('opening_stock') : 0,
            'storage_location' => $this->filled('storage_location') ? trim((string) $this->input('storage_location')) : null,
            'description' => $this->filled('description') ? trim((string) $this->input('description')) : null,
        ]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'code' => [
                'nullable',
                'string',
                'regex:/^[A-Z0-9][A-Z0-9._-]{2,29}$/',
                Rule::unique(Product::class, 'code'),
            ],
            'name' => ['required', 'string', 'min:3', 'max:150'],
            'category_id' => [
                'required',
                'integer',
                Rule::exists(Category::class, 'id')->where('is_active', true),
            ],
            'unit_id' => [
                'required',
                'integer',
                Rule::exists(Unit::class, 'id')->where('is_active', true),
            ],
            'main_supplier_id' => [
                'nullable',
                'integer',
                Rule::exists(Supplier::class, 'id')->where('is_active', true),
            ],
            'purchase_price' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'selling_price' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
            'min_stock' => ['required', 'numeric', 'min:0', 'max:999999999999.999'],
            'opening_stock' => ['nullable', 'numeric', 'min:0', 'max:999999999999.999'],
            'storage_location' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'photo' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
                'dimensions:min_width=100,min_height=100,max_width=4000,max_height=4000',
            ],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $unitId = $this->input('unit_id');
            if ($unitId) {
                $unit = Unit::find($unitId);
                if ($unit) {
                    $this->validateDecimalField($validator, $unit, 'min_stock', 'Stok minimum');
                    $this->validateDecimalField($validator, $unit, 'opening_stock', 'Stok awal');
                }
            }
        });
    }

    protected function validateDecimalField(Validator $validator, Unit $unit, string $field, string $label): void
    {
        $value = $this->input($field);
        if ($value === null || $value === '') {
            return;
        }

        $numericVal = (float) $value;
        if ($numericVal < 0) {
            $validator->errors()->add($field, "{$label} tidak boleh bernilai negatif.");

            return;
        }

        if (! $unit->allow_decimal) {
            if ($numericVal != (int) $numericVal) {
                $validator->errors()->add($field, "{$label} harus bilangan bulat untuk satuan {$unit->name} ({$unit->symbol}).");
            }
        } else {
            $str = (string) $value;
            $parts = explode('.', $str);
            if (isset($parts[1]) && strlen($parts[1]) > 3) {
                $validator->errors()->add($field, "{$label} maksimal memiliki 3 angka desimal untuk satuan {$unit->name}.");
            }
        }
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.regex' => 'Format kode barang harus 3-30 karakter alfanumerik (dapat memuat titik, strip, garis bawah).',
            'code.unique' => 'Kode barang sudah digunakan.',
            'name.required' => 'Nama barang wajib diisi.',
            'name.min' => 'Nama barang minimal 3 karakter.',
            'name.max' => 'Nama barang maksimal 150 karakter.',
            'category_id.required' => 'Kategori barang wajib dipilih.',
            'category_id.exists' => 'Kategori yang dipilih tidak valid atau sudah nonaktif.',
            'unit_id.required' => 'Satuan barang wajib dipilih.',
            'unit_id.exists' => 'Satuan yang dipilih tidak valid atau sudah nonaktif.',
            'main_supplier_id.exists' => 'Supplier utama yang dipilih tidak valid atau sudah nonaktif.',
            'purchase_price.numeric' => 'Harga beli harus berupa angka.',
            'purchase_price.min' => 'Harga beli tidak boleh negatif.',
            'selling_price.numeric' => 'Harga jual harus berupa angka.',
            'selling_price.min' => 'Harga jual tidak boleh negatif.',
            'min_stock.required' => 'Stok minimum wajib diisi.',
            'min_stock.numeric' => 'Stok minimum harus berupa angka.',
            'min_stock.min' => 'Stok minimum tidak boleh negatif.',
            'opening_stock.numeric' => 'Stok awal harus berupa angka.',
            'opening_stock.min' => 'Stok awal tidak boleh negatif.',
            'photo.file' => 'File foto barang harus berupa file valid.',
            'photo.mimes' => 'Format foto barang harus JPG, JPEG, PNG, atau WebP.',
            'photo.max' => 'Ukuran file foto barang maksimal 2 MB.',
            'photo.dimensions' => 'Dimensi foto barang harus antara 100x100 hingga 4000x4000 piksel.',
        ];
    }
}
