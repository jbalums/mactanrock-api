<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Category::query()->truncate();

        $categories = [
            ['name' => 'Electrical'],
            ['name' => 'Hardware'],
            ['name' => 'Plumbing'],
            ['name' => 'Equipment'],
            ['name' => 'Tools'],
            ['name' => 'Consumables'],
            ['name' => 'Construction Materials'],
            ['name' => 'Minerals'],
            ['name' => 'Laboratory Apparatus'],
            ['name' => 'Chemicals'],
            ['name' => 'Reagents'],
            ['name' => 'Raw materials'],
            ['name' => 'Finished products'],
            ['name' => 'Packaging & Labels'],
            ['name' => 'Trading items'],
        ];


        Category::query()->insert($categories);

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

    }
}
