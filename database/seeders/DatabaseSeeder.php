<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Create development testing user
        User::create([
            'first_name' => 'Gleb',
            'last_name' => 'Rodin',
            'phone' => null,
            'phone_extension' => null,
            'email' => 'rodin.gleb@gmail.com',
            'password' => Hash::make('rodin1988'),
        ]);
        $this->command->info('Admin user created.');
    }
}

