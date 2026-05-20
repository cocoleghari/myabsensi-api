<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Superadmin
        User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@app.com',
            'password' => Hash::make('pass123'),
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        // Admin
        User::create([
            'name' => 'Admin SDM',
            'email' => 'admin@app.com',
            'password' => Hash::make('pass12345'),
            'role' => 'admin',
            'company_id' => 1,
            'is_active' => true,
        ]);

        // Beberapa karyawan dummy (untuk testing)
        // User::factory()->count(10)->create();
    }
}
