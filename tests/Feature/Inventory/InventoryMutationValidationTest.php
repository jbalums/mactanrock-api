<?php

namespace Tests\Feature\Inventory;

use App\Enums\UserType;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\InventoryLocation;
use App\Models\Product;
use App\Models\Requisition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InventoryMutationValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_correction_rejects_zero_amount(): void
    {
        [$user, $product] = $this->createInventoryFixture(quantity: 5);

        Sanctum::actingAs($user);

        $this->patchJson('/api/inventory/inventory-correction', [
            'product_id' => $product->id,
            'correction_amount' => 0,
            'correction_reason' => 'Zero correction',
        ])->assertStatus(422)->assertJsonValidationErrors(['correction_amount']);
    }

    public function test_inventory_correction_rejects_negative_amount_beyond_available_stock(): void
    {
        [$user, $product] = $this->createInventoryFixture(quantity: 3);

        Sanctum::actingAs($user);

        $this->patchJson('/api/inventory/inventory-correction', [
            'product_id' => $product->id,
            'correction_amount' => -4,
            'correction_reason' => 'Too much stock out',
        ])->assertStatus(422)->assertJsonValidationErrors(['correction_amount']);
    }

    public function test_repack_rejects_same_input_and_output_product(): void
    {
        [$user, $product] = $this->createInventoryFixture(quantity: 5);

        Sanctum::actingAs($user);

        $this->postJson('/api/inventory/repack', [
            'product_id' => $product->id,
            'qty' => 2,
            'output_product_id' => $product->id,
            'output_qty' => 2,
        ])->assertStatus(422)->assertJsonValidationErrors(['output_product_id']);
    }

    public function test_repack_rejects_quantity_beyond_available_stock(): void
    {
        [$user, $product] = $this->createInventoryFixture(quantity: 2);
        $outputProduct = $this->createProduct();

        Sanctum::actingAs($user);

        $this->postJson('/api/inventory/repack', [
            'product_id' => $product->id,
            'qty' => 3,
            'output_product_id' => $outputProduct->id,
            'output_qty' => 1,
        ])->assertStatus(422)->assertJsonValidationErrors(['qty']);
    }

    public function test_beginning_balance_requires_positive_quantity(): void
    {
        [$user, $product, $inventoryLocation] = $this->createInventoryFixture(quantity: 1);

        Sanctum::actingAs($user);

        $this->patchJson("/api/inventory/beginning-balance/{$inventoryLocation->id}", [
            'product_id' => $product->id,
            'qty' => 0,
            'price' => 25,
        ])->assertStatus(422)->assertJsonValidationErrors(['qty']);
    }

    public function test_hidden_correction_rejects_invalid_movement(): void
    {
        [$user, $product] = $this->createInventoryFixture(quantity: 5);
        $requisition = $this->createRequisition($user);

        Sanctum::actingAs($user);

        $this->postJson('/api/inventory/AUzNo13OhD1ONaRO/correction', [
            'id' => $requisition->id,
            'product_id' => $product->id,
            'qty' => 1,
            'movement' => 'sideways',
        ])->assertStatus(422)->assertJsonValidationErrors(['movement']);
    }

    private function createInventoryFixture(int $quantity): array
    {
        $branch = $this->createBranch();
        $product = $this->createProduct();
        $user = User::factory()->create([
            'firstname' => 'Inventory',
            'lastname' => 'Admin',
            'middlename' => '',
            'contact' => '09123456789',
            'user_type' => UserType::ADMIN->value,
            'email' => fake()->unique()->safeEmail(),
            'username' => fake()->unique()->userName(),
            'branch_id' => $branch->id,
        ]);

        $inventoryLocation = InventoryLocation::query()->create([
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'price' => 100,
            'total_quantity' => $quantity,
            'quantity' => $quantity,
        ]);

        Inventory::query()->create([
            'inventory_location_id' => $inventoryLocation->id,
            'quantity' => $quantity,
            'batch' => 1,
            'receive_id' => $user->id,
            'product_id' => $product->id,
        ]);

        return [$user, $product, $inventoryLocation];
    }

    private function createRequisition(User $user): Requisition
    {
        $requisition = new Requisition();
        $requisition->project_name = 'Validation requisition';
        $requisition->status = 'accepted';
        $requisition->branch_id = $user->branch_id;
        $requisition->user_id = $user->id;
        $requisition->accepted_by_id = $user->id;
        $requisition->purpose = 'project_plant';
        $requisition->account_code = 'ACC-001';
        $requisition->project_code = 'PRJ-001';
        $requisition->save();

        return $requisition;
    }

    private function createBranch(): Branch
    {
        return Branch::query()->create([
            'name' => 'Inventory Branch ' . fake()->unique()->numerify('###'),
            'address' => 'Cebu City',
            'code' => strtoupper(fake()->unique()->bothify('BR-######')),
        ]);
    }

    private function createProduct(): Product
    {
        $category = Category::query()->create([
            'name' => 'Validation Category ' . fake()->unique()->numerify('###'),
        ]);

        return Product::query()->create([
            'name' => 'Validation Product ' . fake()->unique()->numerify('###'),
            'code' => strtoupper(fake()->unique()->bothify('PRD-######')),
            'description' => 'Validation product',
            'unit_measurement' => 'PCS',
            'unit_value' => 1,
            'stock_low_level' => 0,
            'reorder_point' => 0,
            'brand' => 'MRII',
            'category_id' => $category->id,
            'account_code' => 'ACC-001',
        ]);
    }
}
