<?php

namespace Database\Seeders;

use App\Models\InventoryTransaction;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductIdTransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $no_product_ids = InventoryTransaction::query()->whereNull('product_id')->get();
        foreach ($no_product_ids as $transaction) {
            $transaction->product_id = $transaction->inventory->product_id;
            $transaction->save();
        }
    }
}
