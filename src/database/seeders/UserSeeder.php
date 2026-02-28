<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'example1@mail.net'],
            [
                'full_name' => 'User 1',
                'password' => Hash::make('password'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'example2@mail.net'],
            [
                'full_name' => 'User 2',
                'password' => Hash::make('password'),
            ]
        );
    }
}
