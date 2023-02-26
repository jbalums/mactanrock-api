<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Enums\UserType;
use App\Models\Branch;
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
        $main_warehouse = Branch::query()->create([
            'name' => 'Main Warehouse',
            'address' => '3rd Floor FCB Financial Center Building A.C. Cortes Ave. Mandaue City, Cebu, Philippines 6014',
            'code' => 'MW-000001',
        ]);
        $admin = User::query()->create([
             'firstname' => 'Super',
             'lastname' => 'Admin',
             'contact' => '09123456789',
             'middlename' => ' ',
             'user_type' => UserType::ADMIN,
             'email' => 'admin@mrii.com',
             'username' => 'super-admin-mrii',
             'branch_id' => 1,
             'password' => bcrypt('password'),
        ]);
    }
}
