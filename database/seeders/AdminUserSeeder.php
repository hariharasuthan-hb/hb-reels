<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@gym.com'], // Search by email
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'phone' => '1234567890',
                'age' => 30,
                'gender' => 'male',
            ]
        );

        $admin->assignRole('admin');
    }
}
