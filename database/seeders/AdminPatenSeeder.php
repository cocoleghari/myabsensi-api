<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AdminPatenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Cek apakah admin sudah ada
        $admin = User::where('email', 'superadmin@absensi.com')->first();

        if (! $admin) {
            // Buat admin paten
            $admin = User::create([
                'name' => 'Super Admin',
                'email' => 'superadmin@absensi.com',
                'password' => Hash::make('Admin@12345'),
                'role' => 'admin',
            ]);

            Log::info('Admin paten created:', ['id' => $admin->id, 'email' => $admin->email]);
            $this->command->info('✅ Admin paten berhasil dibuat!');
            $this->command->info('📧 Email: superadmin@absensi.com');
            $this->command->info('🔑 Password: Admin@12345');
        } else {
            $this->command->info('ℹ️ Admin paten sudah ada.');
        }
    }
}
