<?php

namespace Tests\Concerns;

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

trait CreatesInventoryFixtures
{
    protected function createBranch(string $prefix = 'Branch'): Branch
    {
        return Branch::query()->create([
            'name' => $prefix . ' ' . fake()->unique()->numerify('###'),
            'address' => 'Cebu City',
            'code' => strtoupper(fake()->unique()->bothify(substr($prefix, 0, 2) . '-######')),
        ]);
    }

    protected function createProduct(array $overrides = []): Product
    {
        $category = Category::query()->create([
            'name' => 'Category ' . fake()->unique()->numerify('###'),
        ]);

        return Product::query()->create(array_merge([
            'name' => 'Product ' . fake()->unique()->numerify('###'),
            'code' => strtoupper(fake()->unique()->bothify('PRD-######')),
            'description' => 'Fixture product',
            'unit_measurement' => 'PCS',
            'unit_value' => 1,
            'stock_low_level' => 0,
            'reorder_point' => 0,
            'brand' => 'MRII',
            'category_id' => $category->id,
            'account_code' => 'ACC-001',
        ], $overrides));
    }

    protected function createUser(Branch $branch, string $role = UserType::ADMIN->value, array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'firstname' => 'Test',
            'lastname' => 'User',
            'middlename' => '',
            'contact' => '09123456789',
            'user_type' => $role,
            'email' => fake()->unique()->safeEmail(),
            'username' => fake()->unique()->userName(),
            'branch_id' => $branch->id,
        ], $overrides));
    }

    protected function seedInventory(Product $product, Branch $branch, User $owner, int $quantity, int $price = 100): array
    {
        $inventoryLocation = InventoryLocation::query()->create([
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'price' => $price,
            'total_quantity' => $quantity,
            'quantity' => $quantity,
        ]);

        $inventory = Inventory::query()->create([
            'inventory_location_id' => $inventoryLocation->id,
            'quantity' => $quantity,
            'batch' => 1,
            'receive_id' => $owner->id,
            'product_id' => $product->id,
            'price' => $price,
        ]);

        return [$inventoryLocation, $inventory];
    }

    protected function createRequisition(User $requester, array $overrides = []): Requisition
    {
        $requisition = new Requisition();
        $requisition->project_name = $overrides['project_name'] ?? 'Fixture Requisition';
        $requisition->project_code = $overrides['project_code'] ?? 'PRJ-001';
        $requisition->status = $overrides['status'] ?? 'pending';
        $requisition->branch_id = $overrides['branch_id'] ?? $requester->branch_id;
        $requisition->user_id = $overrides['user_id'] ?? $requester->id;
        $requisition->accepted_by_id = $overrides['accepted_by_id'] ?? null;
        $requisition->purpose = $overrides['purpose'] ?? 'project_plant';
        $requisition->account_code = $overrides['account_code'] ?? 'ACC-001';
        $requisition->issuance_status = $overrides['issuance_status'] ?? '';
        $requisition->request_from_branch_id = $overrides['request_from_branch_id'] ?? null;
        $requisition->needed_at = $overrides['needed_at'] ?? now()->toDateString();
        $requisition->save();

        return $requisition;
    }

    protected function createRequisitionDetail(Requisition $requisition, Branch $location, array $overrides = []): RequisitionDetail
    {
        return RequisitionDetail::query()->create(array_merge([
            'location_id' => $location->id,
            'requisition_id' => $requisition->id,
            'status' => 'pending',
            'account_code' => $requisition->account_code,
        ], $overrides));
    }

    protected function createRequisitionItem(RequisitionDetail $detail, Product $product, array $overrides = []): RequisitionItem
    {
        return RequisitionItem::query()->create(array_merge([
            'requisition_detail_id' => $detail->id,
            'request_quantity' => 1,
            'full_filled_quantity' => 0,
            'product_id' => $product->id,
            'status' => 'incomplete',
            'used_qty' => 0,
            'returned_qty' => 0,
        ], $overrides));
    }
}
