<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name'     => 'System Admin',
                'password' => Hash::make('password123'),
                'role'     => UserRole::ADMIN,
            ]
        );

        // Fixed Test User 1
        User::firstOrCreate(
            ['email' => 'user1@example.com'],
            [
                'name'     => 'John Doe',
                'password' => Hash::make('password123'),
                'role'     => UserRole::USER,
            ]
        );

        // Fixed Test User 2
        User::firstOrCreate(
            ['email' => 'user2@example.com'],
            [
                'name'     => 'Jane Smith',
                'password' => Hash::make('password123'),
                'role'     => UserRole::USER,
            ]
        );

        // Generate 5 additional random users using the factory
        User::factory()->count(5)->create([
            'role' => UserRole::USER,
        ]);
    }
}
