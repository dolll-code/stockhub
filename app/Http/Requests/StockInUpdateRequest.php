<?php

namespace App\Http\Requests;

use App\Enums\StockInSource;
use App\Enums\TransactionStatus;
use App\Exceptions\InvalidStateTransitionException;
use App\Models\Product;
use App\Models\Setting;
use App\Models\StockIn;
use App\Models\Supplier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StockInUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var StockIn|null $stockIn */
        $stockIn = $this->route('stock_in');
        if (! $stockIn) {
            return false;
        }

        if (! $this->user()?->can('stock-ins.create')) {
            return false;
        }

        $user = $this->user();
        if ($user->hasRole('staff_gudang') && (int) $stockIn->created_by !== (int) $user->id) {
            abort(404, 'Data transaksi tidak ditemukan.');
        }

        if ($stockIn->status !== TransactionStatus::DRAFT) {
            throw new InvalidStateTransitionException('Transaksi confirmed tidak dapat diubah (BR-12).');
        }

        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'reference_document' => $this->filled('reference_document') ? trim((string) $this->input('reference_document')) : null,
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
            'source_type' => [
                'required',
                'string',
                Rule::enum(StockInSource::class),
            ],
            'supplier_id' => [
                Rule::requiredIf(fn () => $this->input('source_type') === StockInSource::PURCHASE->value),
                'nullable',
                'integer',
                Rule::exists(Supplier::class, 'id')->where('is_active', true),
            ],
            'reference_document' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_id' => [
                'required',
                'integer',
                Rule::exists(Product::class, 'id')->where('is_active', true),
                'distinct',
            ],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:999999999.999'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
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
        $maxDays = (int) Setting::getValue('max_backdate_days', '7');

        return [
            'transaction_date.required' => 'Tanggal transaksi wajib diisi.',
            'transaction_date.before_or_equal' => 'Tanggal transaksi tidak boleh melebihi hari ini.',
            'transaction_date.after_or_equal' => "Tanggal transaksi melebihi batas tanggal lampau yang diizinkan (maksimal {$maxDays} hari yang lalu).",
            'source_type.required' => 'Sumber barang masuk wajib dipilih.',
            'supplier_id.required' => 'Supplier wajib dipilih untuk barang masuk bersumber pembelian (purchase).',
            'supplier_id.exists' => 'Supplier yang dipilih tidak valid atau sudah nonaktif.',
            'reference_document.max' => 'Nomor dokumen referensi maksimal 100 karakter.',
            'notes.max' => 'Catatan maksimal 1000 karakter.',
            'items.required' => 'Detail barang masuk wajib diisi minimal 1 baris.',
            'items.min' => 'Detail barang masuk minimal memuat 1 item barang.',
            'items.max' => 'Detail barang masuk maksimal memuat 100 item barang.',
            'items.*.product_id.required' => 'Barang wajib dipilih pada setiap baris.',
            'items.*.product_id.exists' => 'Barang yang dipilih tidak valid atau sudah nonaktif.',
            'items.*.product_id.distinct' => 'Barang tidak boleh duplikat dalam satu transaksi dokumen.',
            'items.*.quantity.required' => 'Kuantitas barang wajib diisi.',
            'items.*.quantity.gt' => 'Kuantitas barang harus lebih besar dari 0.',
        ];
    }
}
