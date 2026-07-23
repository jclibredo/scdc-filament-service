<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;


    public function run(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'jclibredo@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'status' => true,
        ]);

        // Seed the relation directly right here
        UserPermission::create([
            'user_id' => $admin->id,
            'module' => 'SUPERADMIN',
        ]);
    }
}
