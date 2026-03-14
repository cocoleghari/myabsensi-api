<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AdminPatenSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'superadmin@absensi.com')->first();

        if (! $admin) {
            $admin = User::create([
                'name' => 'Super Admin',
                'email' => 'superadmin@absensi.com',
                'password' => Hash::make('Admin@12345'),
                'role' => 'admin',
            ]);

            Log::info('Admin paten created:', ['id' => $admin->id, 'email' => $admin->email]);
            $this->command->info('Admin paten berhasil dibuat!');
            $this->command->info('Email: superadmin@absensi.com');
            $this->command->info('Password: Admin@12345');
        } else {
            $this->command->info('ℹAdmin paten sudah ada.');
        }
    }
}
