<?php

namespace App\Http\Requests;

use App\Enums\AdjustmentReason;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StockAdjustmentStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('adjustments.create') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'notes' => trim((string) $this->input('notes')),
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
            'adjustment_date' => [
                'required',
                'date',
                "before_or_equal:{$today}",
                "after_or_equal:{$oldestDate}",
            ],
            'product_id' => [
                'required',
                'integer',
                Rule::exists(Product::class, 'id')->where('is_active', true),
            ],
            'expected_system_qty' => ['required', 'numeric', 'min:0'],
            'physical_qty' => ['required', 'numeric', 'min:0', 'max:999999999.999'],
            'reason_code' => [
                'required',
                'string',
                Rule::enum(AdjustmentReason::class),
            ],
            'notes' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $productId = $this->input('product_id');
            $physicalQty = $this->input('physical_qty');

            if ($productId && $physicalQty !== null) {
                $product = Product::with('unit')->find($productId);
                if ($product) {
                    $unit = $product->unit;
                    $qty = (string) $physicalQty;

                    if (! $unit->allow_decimal) {
                        if (bccomp($qty, (string) (int) (float) $qty, 3) !== 0) {
                            $validator->errors()->add('physical_qty', "Stok fisik barang {$product->name} harus berupa bilangan bulat untuk satuan {$unit->name} ({$unit->symbol}).");
                        }
                    } else {
                        $parts = explode('.', $qty);
                        if (isset($parts[1]) && strlen($parts[1]) > 3) {
                            $validator->errors()->add('physical_qty', "Stok fisik barang {$product->name} maksimal memiliki 3 angka di belakang koma.");
                        }
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
            'adjustment_date.required' => 'Tanggal penyesuaian wajib diisi.',
            'adjustment_date.before_or_equal' => 'Tanggal penyesuaian tidak boleh melebihi hari ini.',
            'adjustment_date.after_or_equal' => 'Tanggal penyesuaian melebihi batas tanggal lampau yang diizinkan.',
            'product_id.required' => 'Barang yang akan disesuaikan wajib dipilih.',
            'product_id.exists' => 'Barang yang dipilih tidak valid atau sudah nonaktif.',
            'expected_system_qty.required' => 'Nilai stok sistem wajib disertakan.',
            'physical_qty.required' => 'Hasil hitung stok fisik wajib diisi.',
            'physical_qty.min' => 'Stok fisik tidak boleh bernilai negatif.',
            'reason_code.required' => 'Alasan penyesuaian stok opname wajib dipilih.',
            'notes.required' => 'Keterangan / penjelasan selisih fisik wajib diisi.',
            'notes.min' => 'Keterangan penyesuaian minimal 10 karakter.',
            'notes.max' => 'Keterangan penyesuaian maksimal 1000 karakter.',
        ];
    }
}
