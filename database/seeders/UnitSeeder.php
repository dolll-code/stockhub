<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            // Satuan Bulat (allow_decimal = false)
            [
                'name' => 'Pieces',
                'symbol' => 'pcs',
                'description' => 'Satuan hitung barang bijian/satuan tunggal',
                'allow_decimal' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Unit',
                'symbol' => 'unit',
                'description' => 'Satuan perangkat atau unit lengkap',
                'allow_decimal' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Box',
                'symbol' => 'box',
                'description' => 'Kemasan kotak kecil',
                'allow_decimal' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Karton',
                'symbol' => 'ktn',
                'description' => 'Kemasan karton besar/kardus pengiriman',
                'allow_decimal' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Pack',
                'symbol' => 'pack',
                'description' => 'Kemasan bundel/paket',
                'allow_decimal' => false,
                'is_active' => true,
            ],

            // Satuan Desimal (allow_decimal = true)
            [
                'name' => 'Kilogram',
                'symbol' => 'kg',
                'description' => 'Satuan massa/berat kilogram',
                'allow_decimal' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Gram',
                'symbol' => 'g',
                'description' => 'Satuan massa/berat gram',
                'allow_decimal' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Liter',
                'symbol' => 'L',
                'description' => 'Satuan volume zat cair',
                'allow_decimal' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Meter',
                'symbol' => 'm',
                'description' => 'Satuan panjang linier',
                'allow_decimal' => true,
                'is_active' => true,
            ],
        ];

        foreach ($units as $unit) {
            Unit::updateOrCreate(
                ['symbol' => $unit['symbol']],
                $unit
            );
        }
    }
}
