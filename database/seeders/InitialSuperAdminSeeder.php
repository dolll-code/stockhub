<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class InitialSuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = env('INITIAL_ADMIN_EMAIL', 'admin@example.com');
        $user = User::query()->where('email', $email)->first();

        if ($user) {
            $user->is_active = true;
            $user->save();
            $user->assignRole('super_admin');

            if ($this->command) {
                $this->command->info("Super Admin dengan email [{$email}] sudah ada. Role super_admin dipastikan aktif.");
            }

            return;
        }

        // Generate strong one-time random password meeting §5.1 requirements (>=10 chars, uppercase, lowercase, numbers)
        $oneTimePassword = Str::random(10).'A1!';

        $user = User::create([
            'name' => 'Super Administrator',
            'email' => $email,
            'password' => Hash::make($oneTimePassword),
            'is_active' => true,
            'must_change_password' => true,
            'email_verified_at' => now(),
        ]);

        $user->assignRole('super_admin');

        if ($this->command) {
            $this->command->warn('=====================================================');
            $this->command->info('Akun Super Admin berhasil dibuat!');
            $this->command->info("Email:    {$email}");
            $this->command->info("Password: {$oneTimePassword}");
            $this->command->warn('Harap catat password sekali pakai di atas. Pengguna WAJIB mengganti password pada login pertama.');
            $this->command->warn('=====================================================');
        }
    }
}
