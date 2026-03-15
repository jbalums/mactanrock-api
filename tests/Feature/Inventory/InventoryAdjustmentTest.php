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
use App\Services\InventoryServices;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Request;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InventoryAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_positive_stock_adjustments_increase_inventory_and_location_quantities(): void
    {
        [$user, $product, $inventoryLocation, $inventory] = $this->createInventoryFixture(10);

        $this->bindAuthenticatedRequestUser($user);

        app(InventoryServices::class)->stockAdjustments($product->id, 5, $this->inventoryData($user), $user->branch_id);

        $this->assertSame(15, (int) $inventoryLocation->fresh()->quantity);
        $this->assertSame(15, (int) $inventoryLocation->fresh()->total_quantity);
        $this->assertSame(15, (int) $inventory->fresh()->quantity);
        $this->assertDatabaseHas('inventory_transactions', [
            'inventory_id' => $inventory->id,
            'product_id' => $product->id,
            'movement' => 'in',
            'quantity' => 5,
        ]);
    }

    public function test_negative_stock_adjustments_decrease_inventory_and_location_quantities(): void
    {
        [$user, $product, $inventoryLocation, $inventory] = $this->createInventoryFixture(10);

        $this->bindAuthenticatedRequestUser($user);

        app(InventoryServices::class)->stockAdjustments($product->id, -4, $this->inventoryData($user), $user->branch_id);

        $this->assertSame(6, (int) $inventoryLocation->fresh()->quantity);
        $this->assertSame(6, (int) $inventoryLocation->fresh()->total_quantity);
        $this->assertSame(6, (int) $inventory->fresh()->quantity);
        $this->assertDatabaseHas('inventory_transactions', [
            'inventory_id' => $inventory->id,
            'product_id' => $product->id,
            'movement' => 'out',
            'quantity' => 4,
        ]);
    }

    public function test_stock_out_decrements_both_inventory_location_counters(): void
    {
        [$user, $product, $inventoryLocation, $inventory] = $this->createInventoryFixture(10);

        $this->bindAuthenticatedRequestUser($user);

        app(InventoryServices::class)->stockOut($product->id, 3, $this->inventoryData($user), $user->branch_id);

        $this->assertSame(7, (int) $inventoryLocation->fresh()->quantity);
        $this->assertSame(7, (int) $inventoryLocation->fresh()->total_quantity);
        $this->assertSame(7, (int) $inventory->fresh()->quantity);
        $this->assertDatabaseHas('inventory_transactions', [
            'inventory_id' => $inventory->id,
            'product_id' => $product->id,
            'movement' => 'out',
            'quantity' => 3,
        ]);
    }

    public function test_hidden_correction_endpoint_updates_inventory_and_location_quantities(): void
    {
        [$user, $product, $inventoryLocation, $inventory] = $this->createInventoryFixture(10);

        $requisition = new Requisition();
        $requisition->project_name = 'Correction request';
        $requisition->status = 'accepted';
        $requisition->branch_id = $user->branch_id;
        $requisition->user_id = $user->id;
        $requisition->accepted_by_id = $user->id;
        $requisition->purpose = 'finished_goods';
        $requisition->account_code = 'ACC-001';
        $requisition->project_code = 'PRJ-001';
        $requisition->save();

        Sanctum::actingAs($user);

        $this->postJson('/api/inventory/AUzNo13OhD1ONaRO/correction', [
            'id' => $requisition->id,
            'product_id' => $product->id,
            'qty' => 2,
            'movement' => 'out',
        ])->assertOk();

        $this->assertSame(8, (int) $inventoryLocation->fresh()->quantity);
        $this->assertSame(8, (int) $inventoryLocation->fresh()->total_quantity);
        $this->assertSame(8, (int) $inventory->fresh()->quantity);
    }

    private function bindAuthenticatedRequestUser(User $user): void
    {
        Sanctum::actingAs($user);
        request()->setUserResolver(fn () => $user);
    }

    private function inventoryData(User $user): array
    {
        return [
            'transacted_by_id' => $user->id,
            'accepted_by_id' => $user->id,
            'from_branch_id' => $user->branch_id,
            'to_branch_id' => $user->branch_id,
            'correction_reason' => 'inventory correction test',
            'description' => 'inventory adjustment test',
        ];
    }

    private function createInventoryFixture(int $quantity): array
    {
        $branch = Branch::query()->create([
            'name' => 'Main Warehouse',
            'address' => 'Cebu City',
            'code' => strtoupper(fake()->bothify('BR-######')),
        ]);

        $category = Category::query()->create([
            'name' => 'Inventory Test Category',
        ]);

        $product = Product::query()->create([
            'name' => 'Inventory Test Product',
            'code' => strtoupper(fake()->bothify('PRD-######')),
            'description' => 'Inventory test product',
            'unit_measurement' => 'PCS',
            'unit_value' => 1,
            'stock_low_level' => 0,
            'reorder_point' => 0,
            'brand' => 'MRII',
            'category_id' => $category->id,
            'account_code' => 'ACC-001',
        ]);

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

        $inventory = Inventory::query()->create([
            'inventory_location_id' => $inventoryLocation->id,
            'quantity' => $quantity,
            'batch' => 1,
            'receive_id' => $user->id,
            'product_id' => $product->id,
        ]);

        return [$user, $product, $inventoryLocation, $inventory];
    }
}
