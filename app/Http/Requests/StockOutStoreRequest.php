<?php

namespace App\Http\Requests;

use App\Enums\StockOutPurpose;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StockOutStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('stock-outs.create') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'destination' => trim((string) $this->input('destination')),
            'notes' => $this->filled('notes') ? trim((string) $this->input('notes')) : null,
        ]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $maxDays = (int) Setting::getValue('max_backdate_days', '7');
        $oldestDate = now()->subDays($maxDays)->toDateString();
        $today = now()->toDateString();

        return [
            'transaction_date' => [
                'required',
                'date',
                "before_or_equal:{$today}",
                "after_or_equal:{$oldestDate}",
            ],
            'purpose_type' => [
                'required',
                'string',
                Rule::enum(StockOutPurpose::class),
            ],
            'destination' => ['required', 'string', 'min:3', 'max:150'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_id' => [
                'required',
                'integer',
                Rule::exists(Product::class, 'id')->where('is_active', true),
                'distinct',
            ],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:999999999.999'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $items = $this->input('items', []);
            if (! is_array($items)) {
                return;
            }

            $productIds = collect($items)->pluck('product_id')->filter()->unique()->all();
            $products = Product::with('unit')->whereIn('id', $productIds)->get()->keyBy('id');

            foreach ($items as $index => $item) {
                if (! isset($item['product_id']) || ! isset($item['quantity'])) {
                    continue;
                }

                $product = $products->get($item['product_id']);
                if (! $product) {
                    continue;
                }

                $unit = $product->unit;
                $qty = (string) $item['quantity'];

                if (! $unit->allow_decimal) {
                    if (bccomp($qty, (string) (int) (float) $qty, 3) !== 0) {
                        $validator->errors()->add("items.{$index}.quantity", "Kuantitas barang {$product->name} harus bilangan bulat untuk satuan {$unit->name} ({$unit->symbol}).");
                    }
                } else {
                    $parts = explode('.', $qty);
                    if (isset($parts[1]) && strlen($parts[1]) > 3) {
                        $validator->errors()->add("items.{$index}.quantity", "Kuantitas barang {$product->name} maksimal memiliki 3 angka di belakang koma.");
                    }
                }
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'transaction_date.required' => 'Tanggal transaksi wajib diisi.',
            'transaction_date.before_or_equal' => 'Tanggal transaksi tidak boleh melebihi hari ini.',
            'transaction_date.after_or_equal' => 'Tanggal transaksi melebihi batas tanggal lampau yang diizinkan.',
            'purpose_type.required' => 'Tujuan peruntukan barang keluar wajib dipilih.',
            'destination.required' => 'Tujuan / penerima barang keluar wajib diisi.',
            'destination.min' => 'Tujuan penerima minimal 3 karakter.',
            'destination.max' => 'Tujuan penerima maksimal 150 karakter.',
            'notes.max' => 'Catatan maksimal 1000 karakter.',
            'items.required' => 'Detail barang keluar wajib diisi minimal 1 baris.',
            'items.min' => 'Detail barang keluar minimal memuat 1 item barang.',
            'items.max' => 'Detail barang keluar maksimal memuat 100 item barang.',
            'items.*.product_id.required' => 'Barang wajib dipilih pada setiap baris.',
            'items.*.product_id.exists' => 'Barang yang dipilih tidak valid atau sudah nonaktif.',
            'items.*.product_id.distinct' => 'Barang tidak boleh duplikat dalam satu transaksi dokumen.',
            'items.*.quantity.required' => 'Kuantitas barang wajib diisi.',
            'items.*.quantity.gt' => 'Kuantitas barang harus lebih besar dari 0.',
        ];
    }
}
