<?php

namespace Tests\Feature\ProjectPlant;

use App\Enums\UserType;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\InventoryLocation;
use App\Models\Product;
use App\Models\Requisition;
use App\Models\RequisitionDetail;
use App\Models\RequisitionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProjectPlantValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_consume_items_rejects_mismatched_payload_lengths(): void
    {
        [$user, $product, $item] = $this->createProjectPlantFixture(stockQuantity: 10, requestQuantity: 5);

        Sanctum::actingAs($user);

        $this->postJson('/api/inventory/consume-items', [
            'requisition_items_id' => [$item->id],
            'product_id' => [$product->id],
            'qty' => [1, 2],
        ])->assertStatus(422)->assertJsonValidationErrors(['qty']);
    }

    public function test_consume_items_rejects_quantity_beyond_remaining_request_quantity(): void
    {
        [$user, $product, $item] = $this->createProjectPlantFixture(
            stockQuantity: 10,
            requestQuantity: 5,
            usedQuantity: 1,
            returnedQuantity: 1
        );

        Sanctum::actingAs($user);

        $this->postJson('/api/inventory/consume-items', [
            'requisition_items_id' => [$item->id],
            'product_id' => [$product->id],
            'qty' => [4],
        ])->assertStatus(422)->assertJsonValidationErrors(['qty.0']);
    }

    public function test_return_items_rejects_quantity_beyond_remaining_returnable_quantity(): void
    {
        [$user, $product, $item] = $this->createProjectPlantFixture(
            stockQuantity: 10,
            requestQuantity: 5,
            usedQuantity: 2,
            returnedQuantity: 1
        );

        Sanctum::actingAs($user);

        $this->postJson('/api/inventory/return-items', [
            'requisition_items_id' => [$item->id],
            'product_id' => [$product->id],
            'qty' => [3],
        ])->assertStatus(422)->assertJsonValidationErrors(['qty.0']);
    }

    public function test_return_items_rejects_quantity_beyond_available_branch_stock(): void
    {
        [$user, $product, $item] = $this->createProjectPlantFixture(
            stockQuantity: 1,
            requestQuantity: 5
        );

        Sanctum::actingAs($user);

        $this->postJson('/api/inventory/return-items', [
            'requisition_items_id' => [$item->id],
            'product_id' => [$product->id],
            'qty' => [2],
        ])->assertStatus(422)->assertJsonValidationErrors(['qty.0']);
    }

    private function createProjectPlantFixture(
        int $stockQuantity,
        int $requestQuantity,
        int $usedQuantity = 0,
        int $returnedQuantity = 0
    ): array {
        $branch = $this->createBranch();
        $product = $this->createProduct();
        $user = User::factory()->create([
            'firstname' => 'Project',
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
            'total_quantity' => $stockQuantity,
            'quantity' => $stockQuantity,
        ]);

        Inventory::query()->create([
            'inventory_location_id' => $inventoryLocation->id,
            'quantity' => $stockQuantity,
            'batch' => 1,
            'receive_id' => $user->id,
            'product_id' => $product->id,
        ]);

        $requisition = new Requisition();
        $requisition->project_name = 'Plant Request';
        $requisition->status = 'accepted';
        $requisition->branch_id = $branch->id;
        $requisition->user_id = $user->id;
        $requisition->accepted_by_id = $user->id;
        $requisition->purpose = 'project_plant';
        $requisition->account_code = 'ACC-PP-001';
        $requisition->project_code = 'PP-001';
        $requisition->save();

        $detail = RequisitionDetail::query()->create([
            'location_id' => $branch->id,
            'requisition_id' => $requisition->id,
            'status' => 'accepted',
            'account_code' => 'ACC-PP-001',
        ]);

        $item = RequisitionItem::query()->create([
            'requisition_detail_id' => $detail->id,
            'request_quantity' => $requestQuantity,
            'full_filled_quantity' => $requestQuantity,
            'product_id' => $product->id,
            'status' => 'completed',
            'used_qty' => $usedQuantity,
            'returned_qty' => $returnedQuantity,
        ]);

        return [$user, $product, $item];
    }

    private function createBranch(): Branch
    {
        return Branch::query()->create([
            'name' => 'Project Branch ' . fake()->unique()->numerify('###'),
            'address' => 'Cebu City',
            'code' => strtoupper(fake()->unique()->bothify('PB-######')),
        ]);
    }

    private function createProduct(): Product
    {
        $category = Category::query()->create([
            'name' => 'Project Validation Category ' . fake()->unique()->numerify('###'),
        ]);

        return Product::query()->create([
            'name' => 'Project Validation Product ' . fake()->unique()->numerify('###'),
            'code' => strtoupper(fake()->unique()->bothify('PP-######')),
            'description' => 'Project validation product',
            'unit_measurement' => 'PCS',
            'unit_value' => 1,
            'stock_low_level' => 0,
            'reorder_point' => 0,
            'brand' => 'MRII',
            'category_id' => $category->id,
            'account_code' => 'ACC-PP-001',
        ]);
    }
}
