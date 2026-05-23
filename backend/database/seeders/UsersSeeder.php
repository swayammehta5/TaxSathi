<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Demo Admin',
                'role' => 'admin',
                'password' => Hash::make('password'),
            ]
        );

        $staff = [
            ['name' => 'Priya Sharma', 'email' => 'priya@example.com'],
            ['name' => 'Rahul Verma', 'email' => 'rahul@example.com'],
            ['name' => 'Anita Patel', 'email' => 'anita@example.com'],
            ['name' => 'Vikram Singh', 'email' => 'vikram@example.com'],
            ['name' => 'Sneha Reddy', 'email' => 'sneha@example.com'],
        ];

        foreach ($staff as $index => $row) {
            User::query()->updateOrCreate(
                ['email' => $row['email']],
                [
                    'name' => $row['name'],
                    'role' => 'staff',
                    'password' => Hash::make('password'),
                ]
            );
        }
    }
}
