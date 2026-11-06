<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('DemoSeeder dilarang keras dijalankan pada lingkungan produksi!');
        }

        // 1. Demo Categories
        $categories = [
            ['name' => 'Elektronik', 'description' => 'Peralatan elektronik dan perangkat keras'],
            ['name' => 'Peralatan Kantor', 'description' => 'Alat tulis kantor dan perlengkapan kerja'],
            ['name' => 'Bahan Baku', 'description' => 'Komponen dan bahan baku produksi'],
            ['name' => 'Makanan dan Minuman', 'description' => 'Persediaan konsumsi dan pantry'],
            ['name' => 'Perlengkapan Kebersihan', 'description' => 'Bahan sanitasi dan kebersihan fasilitas'],
            ['name' => 'Suku Cadang', 'description' => 'Sparepart mesin dan kendaraan operasional'],
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(['name' => $cat['name']], $cat);
        }

        // 2. Demo Suppliers
        $suppliers = [
            [
                'code' => 'SUP-0001',
                'name' => 'PT Sumber Jaya Abadi',
                'contact_name' => 'Budi Santoso',
                'phone' => '081234567890',
                'email' => 'contact@sumberjaya.example.com',
                'address' => 'Jl. Industri Raya No. 12, Kawasan Industri Jababeka, Bekasi',
                'notes' => 'Pemasok utama komponen elektronik dan suku cadang',
                'is_active' => true,
            ],
            [
                'code' => 'SUP-0002',
                'name' => 'CV Berkah Utama',
                'contact_name' => 'Siti Rahmawati',
                'phone' => '082198765432',
                'email' => 'sales@berkahutama.example.com',
                'address' => 'Jl. Mangga Dua No. 45, Jakarta Pusat',
                'notes' => 'Pemasok alat tulis kantor dan perlengkapan kebersihan',
                'is_active' => true,
            ],
        ];

        foreach ($suppliers as $sup) {
            Supplier::firstOrCreate(['code' => $sup['code']], $sup);
        }

        // 3. Demo Users per Role (password: Password123!)
        $demoUsers = [
            [
                'name' => 'Admin Gudang Demo',
                'email' => 'ag@example.com',
                'role' => 'admin_gudang',
            ],
            [
                'name' => 'Staff Gudang Demo',
                'email' => 'sg@example.com',
                'role' => 'staff_gudang',
            ],
            [
                'name' => 'Manajer Gudang Demo',
                'email' => 'mg@example.com',
                'role' => 'manajer',
            ],
        ];

        foreach ($demoUsers as $du) {
            $user = User::firstOrCreate(
                ['email' => $du['email']],
                [
                    'name' => $du['name'],
                    'password' => Hash::make('Password123!'),
                    'is_active' => true,
                    'must_change_password' => false,
                    'email_verified_at' => now(),
                ]
            );
            $user->syncRoles([$du['role']]);
        }

        // 4. Demo Products (current_stock = 0 per Instruction 7; stock balances to be handled via StockService)
        $pcsUnit = Unit::query()->where('symbol', 'pcs')->first();
        $boxUnit = Unit::query()->where('symbol', 'box')->first();
        $kgUnit = Unit::query()->where('symbol', 'kg')->first();

        $elektronikCat = Category::query()->where('name', 'Elektronik')->first();
        $kantorCat = Category::query()->where('name', 'Peralatan Kantor')->first();
        $bahanCat = Category::query()->where('name', 'Bahan Baku')->first();

        $sup1 = Supplier::query()->where('code', 'SUP-0001')->first();
        $sup2 = Supplier::query()->where('code', 'SUP-0002')->first();

        $adminUser = User::query()->where('email', env('INITIAL_ADMIN_EMAIL', 'admin@example.com'))->first()
            ?? User::first();

        if ($pcsUnit && $elektronikCat && $sup1 && $adminUser) {
            $demoProducts = [
                [
                    'code' => 'BRG-0001',
                    'name' => 'Mouse Wireless Logitech M185',
                    'category_id' => $elektronikCat->id,
                    'unit_id' => $pcsUnit->id,
                    'main_supplier_id' => $sup1->id,
                    'purchase_price' => 125000.00,
                    'selling_price' => 175000.00,
                    'current_stock' => 0.000,
                    'min_stock' => 10.000,
                    'storage_location' => 'Rak A-01',
                    'description' => 'Mouse wireless 2.4 GHz hitam',
                    'is_active' => true,
                    'created_by' => $adminUser->id,
                ],
                [
                    'code' => 'BRG-0002',
                    'name' => 'Keyboard Mekanikal Keychron K2',
                    'category_id' => $elektronikCat->id,
                    'unit_id' => $pcsUnit->id,
                    'main_supplier_id' => $sup1->id,
                    'purchase_price' => 850000.00,
                    'selling_price' => 1200000.00,
                    'current_stock' => 0.000,
                    'min_stock' => 5.000,
                    'storage_location' => 'Rak A-02',
                    'description' => 'Keyboard mekanikal wireless 75% layout',
                    'is_active' => true,
                    'created_by' => $adminUser->id,
                ],
            ];

            if ($boxUnit && $kantorCat && $sup2) {
                $demoProducts[] = [
                    'code' => 'BRG-0003',
                    'name' => 'Kertas HVS A4 80gr PaperOne',
                    'category_id' => $kantorCat->id,
                    'unit_id' => $boxUnit->id,
                    'main_supplier_id' => $sup2->id,
                    'purchase_price' => 220000.00,
                    'selling_price' => 260000.00,
                    'current_stock' => 0.000,
                    'min_stock' => 15.000,
                    'storage_location' => 'Rak B-01',
                    'description' => 'Kertas HVS 1 box isi 5 rim',
                    'is_active' => true,
                    'created_by' => $adminUser->id,
                ];
            }

            if ($kgUnit && $bahanCat && $sup1) {
                $demoProducts[] = [
                    'code' => 'BRG-0004',
                    'name' => 'Biji Plastik Polipropilena (PP)',
                    'category_id' => $bahanCat->id,
                    'unit_id' => $kgUnit->id,
                    'main_supplier_id' => $sup1->id,
                    'purchase_price' => 28000.00,
                    'selling_price' => 35000.00,
                    'current_stock' => 0.000,
                    'min_stock' => 100.000,
                    'storage_location' => 'Gudang Bahan C-01',
                    'description' => 'Biji plastik murni kualitas injeksi',
                    'is_active' => true,
                    'created_by' => $adminUser->id,
                ];
            }

            foreach ($demoProducts as $prod) {
                Product::firstOrCreate(['code' => $prod['code']], $prod);
            }
        }
    }
}
