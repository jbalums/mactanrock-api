<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Enums\UserType;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
       $admin = User::query()->create([
            'firstname' => 'MRII',
            'lastname' => 'Admin',
            'contact' => '09978011111',
            'middlename' => 'Main',
            'user_type' => UserType::ADMIN,
            'email' => 'admin@mrii.com',
            'username' => 'mrii',
            'password' => bcrypt('password'),
       ]);
    }
}
