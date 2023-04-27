<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */


class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Product::class;

    public function definition()
    {
        
        $productNames = [
            "ABSORBENT BOOMMESH 4X10",
            "ABSORBENT PAD",
            "FILTER BAG 5 MICRON 18CMX40CM",
            "ACTIVATED CARBON GRANULES",
            "ANTHRACITE CARBON",
            "CARTRIDGE FILTER 50 CMD-BIG BL",
            "GD0HP30E8 FIBR PLEAT FILT CART",
            "FILTER BAG 1MICRON",
            "FILTER BAG SHORT 7X16 1 MICRON",
            "FILTER BAG 5M WSE",
            "FILTER CARBON 5MX30 W/T WSE",
            "UNIT - CARBON BLOCK FILTER SL2",
            "PROPYLENE GLYCOL",
            "GD0HP30E8 POLYPROPYLENE  0.45",
            "GD0HP30E8 POLYPROPYLENE  0.45",
            "ETHYLENE GLYCOL",
            "FILTER CARTRIDGE 5MX20 WSE",
            "FILTER CARTRIDGE 5MX20 WSE",
            "WHATMAN STERLINE MEMBRANE  FIL",
            "FILTER SED 1MX10 SPUND WSE",
            "FILTER SED 1MX10 WOUND WSE",
            "FILTER SED 1MX10 WSE",
            "FILTER SED 1MX20 WOUND WSE",
            "FILTER SED 1MX30 WOUND WSE",
            "FILTER SED 1MX30 WSE",
            "FILTER SED 5MX20 WSE",
            "FILTER SED 5MX20 WSE",
            "FILTER SED 5MX30 SPUND WSE",
            "FILTER SED 5MX4.5 WSE",
            "FILTER MEMB STERIL 47MM,0.45uM",
            "FILTER SEDIMENT FILTER 5M X 10",
            "UNIT - SEDIMENT FILTER SL20",
            "SEDIMENT FILTER 5 MICRON 40",
            "PE05 SMALL BOSS MBBR MEDIA",
            "AA/AMPS",
            "ACH LIQUID 250KG/DRUM",
            "ACH LIQUID 250KG/DRUM",
            "ACID INHIBITOR",
            "ACID INHIBITOR",
            "AIRCOIL CLEANER 20LT/PL",
            "ALUMINUM CHLOROHYDRATE270KG/D",
            "ALUMINUM SULFATE 25KG/BAG",
            "LIQUID ALUMINUM SULFATE/DRUM",
            "AMMONIUM BUFFER SOL'N",
            "AMMONIUM BUFFER SOL'N 500ML/BT",
            "AMMONIA WATER DRUM",
            "AMMONIA WATER WSE",
            "ASCORBIC ACID 25KG/BAG",
            "AWC C-158 (55 GAL/DRUM)",
            "AWC 102 LTR WSE",
            "AWC 103 LTR WSE",
            "AWC 158 LTR WSE 220LT/DRUM",
            "AWC 680 LTR 220KG/DRUM",
            "AWC 751 200LT/DRUM WSE"
        ];
        return [
            'name'=>$this->faker->unique()->randomElement($productNames),
            'code'=>$this->faker->isbn10(),
            'description'=>$this->faker->text,
            'unit_measurement'=>$this->faker->randomElement(['PC','BAG',"UNIT", "PL", 'LT', "KG"]),
            'unit_value'=>$this->faker->randomElement([5,10,15,20,25,30,35,40]),
            'stock_low_level'=>0,
            'reorder_point'=>0,
            'brand'=>$this->faker->company,
            'category_id'=>$this->faker->randomElement([1,2,3,4,5,6,7,8]),
            'account_code'=>$this->faker->isbn10(),
        ];
    }
}
