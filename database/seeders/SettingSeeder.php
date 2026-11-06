<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'company_name',
                'value' => 'Nama Perusahaan',
            ],
            [
                'key' => 'company_address',
                'value' => null,
            ],
            [
                'key' => 'company_phone',
                'value' => null,
            ],
            [
                'key' => 'company_logo_path',
                'value' => null,
            ],
            [
                'key' => 'max_backdate_days',
                'value' => '7',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'updated_at' => now(),
                ]
            );
        }
    }
}
