<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $cbu = [
            ['name' => 'Electrical', 'business_unit' => 'CBU'],
            ['name' => 'Hardware', 'business_unit' => 'CBU'],
            ['name' => 'Plumbing', 'business_unit' => 'CBU'],
            ['name' => 'Equipment', 'business_unit' => 'CBU'],
            ['name' => 'Tools', 'business_unit' => 'CBU'],
            ['name' => 'Consumables', 'business_unit' => 'CBU'],
            ['name' => 'Construction Materials', 'business_unit' => 'CBU'],
            ['name' => 'Minerals', 'business_unit' => 'CBU'],
            ['name' => 'Laboratory Apparatus', 'business_unit' => 'CBU'],
            ['name' => 'Chemicals', 'business_unit' => 'CBU'],
        ];

        $wbu = [


        ];
    }
}
