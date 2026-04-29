<?php

namespace Tests\Feature\Inventory\V2;

use App\Enums\UserType;
use App\Models\InventoryTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesInventoryFixtures;
use Tests\TestCase;

class InventoryApiTest extends TestCase
{
    use CreatesInventoryFixtures;
    use RefreshDatabase;

    public function test_it_lists_inventory_for_the_authenticated_users_branch(): void
    {
        $branch = $this->createBranch('User Branch');
        $otherBranch = $this->createBranch('Other Branch');
        $user = $this->createUser($branch, UserType::EMPLOYEE->value);
        $product = $this->createProduct(['name' => 'Scoped Pump']);
        $otherProduct = $this->createProduct(['name' => 'Other Pump']);

        [$inventoryLocation] = $this->seedInventory($product, $branch, $user, 8, 150);
        [$otherInventoryLocation] = $this->seedInventory($otherProduct, $otherBranch, $user, 20, 200);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v2/inventory?branch_id={$otherBranch->id}");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $inventoryLocation->id)
            ->assertJsonPath('data.0.product.name', 'Scoped Pump')
            ->assertJsonPath('data.0.stock_status', 'in_stock');

        $this->assertNotSame($otherInventoryLocation->id, $response->json('data.0.id'));
    }

    public function test_main_admin_can_filter_inventory_by_branch(): void
    {
        $mainBranch = $this->createBranch('Main');
        $targetBranch = $this->createBranch('Target');
        $admin = $this->createUser($mainBranch, UserType::ADMIN->value);
        $mainProduct = $this->createProduct(['name' => 'Main Chemical']);
        $targetProduct = $this->createProduct(['name' => 'Target Chemical']);

        $this->seedInventory($mainProduct, $mainBranch, $admin, 5, 100);
        [$targetInventoryLocation] = $this->seedInventory($targetProduct, $targetBranch, $admin, 12, 250);

        Sanctum::actingAs($admin);

        $this->getJson("/api/v2/inventory?branch_id={$targetBranch->id}&keyword=Target")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $targetInventoryLocation->id)
            ->assertJsonPath('data.0.branch.id', $targetBranch->id)
            ->assertJsonPath('data.0.inventory_value', 3000);
    }

    public function test_summary_returns_inventory_totals_and_stock_status_counts(): void
    {
        $mainBranch = $this->createBranch('Main');
        $admin = $this->createUser($mainBranch, UserType::ADMIN->value);

        $outProduct = $this->createProduct(['name' => 'Out Item']);
        $reorderProduct = $this->createProduct(['name' => 'Reorder Item']);
        $lowProduct = $this->createProduct(['name' => 'Low Item']);
        $stockedProduct = $this->createProduct(['name' => 'Stocked Item']);

        [$outInventory] = $this->seedInventory($outProduct, $mainBranch, $admin, 0, 100);
        [$reorderInventory] = $this->seedInventory($reorderProduct, $mainBranch, $admin, 2, 50);
        [$lowInventory] = $this->seedInventory($lowProduct, $mainBranch, $admin, 7, 10);
        [$stockedInventory] = $this->seedInventory($stockedProduct, $mainBranch, $admin, 20, 5);

        $outInventory->update(['stock_low_level' => 10, 'reorder_point' => 5]);
        $reorderInventory->update(['stock_low_level' => 10, 'reorder_point' => 5]);
        $lowInventory->update(['stock_low_level' => 10, 'reorder_point' => 5]);
        $stockedInventory->update(['stock_low_level' => 10, 'reorder_point' => 5]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/v2/inventory/summary')
            ->assertOk()
            ->assertJsonPath('data.total_items', 4)
            ->assertJsonPath('data.total_quantity', 29)
            ->assertJsonPath('data.total_inventory_value', 270)
            ->assertJsonPath('data.stock_status_counts.out', 1)
            ->assertJsonPath('data.stock_status_counts.reorder', 1)
            ->assertJsonPath('data.stock_status_counts.low', 1)
            ->assertJsonPath('data.stock_status_counts.in_stock', 1);
    }

    public function test_show_returns_accessible_inventory(): void
    {
        $branch = $this->createBranch('Show Branch');
        $user = $this->createUser($branch, UserType::WAREHOUSE_MAN->value);
        $product = $this->createProduct(['name' => 'Show Valve']);
        [$inventoryLocation] = $this->seedInventory($product, $branch, $user, 9, 75);

        Sanctum::actingAs($user);

        $this->getJson("/api/v2/inventory/{$inventoryLocation->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $inventoryLocation->id)
            ->assertJsonPath('data.product.name', 'Show Valve');
    }

    public function test_show_does_not_expose_other_branch_inventory_to_regular_users(): void
    {
        $branch = $this->createBranch('User Branch');
        $otherBranch = $this->createBranch('Private Branch');
        $user = $this->createUser($branch, UserType::EMPLOYEE->value);
        $otherProduct = $this->createProduct(['name' => 'Private Resin']);
        [$otherInventoryLocation] = $this->seedInventory($otherProduct, $otherBranch, $user, 6, 125);

        Sanctum::actingAs($user);

        $this->getJson("/api/v2/inventory/{$otherInventoryLocation->id}")
            ->assertNotFound();
    }

    public function test_transaction_histories_returns_paginated_product_histories(): void
    {
        $branch = $this->createBranch('History Branch');
        $user = $this->createUser($branch, UserType::WAREHOUSE_MAN->value);
        $product = $this->createProduct(['name' => 'History Product']);
        [, $inventory] = $this->seedInventory($product, $branch, $user, 10, 75);

        $this->createInventoryTransaction($inventory->id, $product->id, $branch->id, $user->id, 'in', 10);
        $outTransaction = $this->createInventoryTransaction($inventory->id, $product->id, $branch->id, $user->id, 'out', 3);
        $inTransaction = $this->createInventoryTransaction($inventory->id, $product->id, $branch->id, $user->id, 'in', 5);

        Sanctum::actingAs($user);

        $this->getJson("/api/v2/inventory/transaction-histories?product_id={$product->id}&page=2&paginate=2")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $inTransaction->id)
            ->assertJsonPath('data.0.quantity_balance', 12)
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.per_page', 2);

        $this->getJson("/api/v2/inventory/transaction-histories?product_id={$product->id}&page=1&paginate=2")
            ->assertOk()
            ->assertJsonPath('data.1.id', $outTransaction->id)
            ->assertJsonPath('data.1.quantity_balance', 7);
    }

    public function test_transaction_histories_supports_sort_by_and_sort_direction(): void
    {
        $branch = $this->createBranch('Sort Branch');
        $user = $this->createUser($branch, UserType::WAREHOUSE_MAN->value);
        $product = $this->createProduct(['name' => 'Sort Product']);
        [, $inventory] = $this->seedInventory($product, $branch, $user, 10, 75);

        $first = $this->createInventoryTransaction($inventory->id, $product->id, $branch->id, $user->id, 'in', 2);
        $second = $this->createInventoryTransaction($inventory->id, $product->id, $branch->id, $user->id, 'out', 1);
        $third = $this->createInventoryTransaction($inventory->id, $product->id, $branch->id, $user->id, 'in', 3);

        Sanctum::actingAs($user);

        $this->getJson("/api/v2/inventory/transaction-histories?product_id={$product->id}&sort_by=id&sort_direction=desc&paginate=3")
            ->assertOk()
            ->assertJsonPath('data.0.id', $third->id)
            ->assertJsonPath('data.1.id', $second->id)
            ->assertJsonPath('data.2.id', $first->id);

        $this->getJson("/api/v2/inventory/transaction-histories?product_id={$product->id}&sort=quantity&sort_direction=desc&paginate=3")
            ->assertOk()
            ->assertJsonPath('data.0.quantity', 3)
            ->assertJsonPath('data.1.quantity', 2)
            ->assertJsonPath('data.2.quantity', 1);
    }

    public function test_transaction_histories_supports_keyword_search(): void
    {
        $branch = $this->createBranch('Keyword Branch');
        $user = $this->createUser($branch, UserType::WAREHOUSE_MAN->value);
        $product = $this->createProduct(['name' => 'Keyword Product']);
        [, $inventory] = $this->seedInventory($product, $branch, $user, 10, 75);

        $matching = $this->createInventoryTransaction($inventory->id, $product->id, $branch->id, $user->id, 'in', 2, 'keyword alpha');
        $this->createInventoryTransaction($inventory->id, $product->id, $branch->id, $user->id, 'out', 1, 'ordinary note');

        Sanctum::actingAs($user);

        $this->getJson("/api/v2/inventory/transaction-histories?product_id={$product->id}&keyword=alpha&paginate=10")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matching->id)
            ->assertJsonPath('data.0.details', 'keyword alpha');
    }

    private function createInventoryTransaction(
        int $inventoryId,
        int $productId,
        int $branchId,
        int $userId,
        string $movement,
        int $quantity,
        string $details = 'v2 transaction history test'
    ): InventoryTransaction {
        $transaction = new InventoryTransaction();
        $transaction->quantity = $quantity;
        $transaction->branch_id = $branchId;
        $transaction->product_id = $productId;
        $transaction->transacted_by_id = $userId;
        $transaction->accepted_by_id = $userId;
        $transaction->movement = $movement;
        $transaction->details = $details;
        $transaction->action = 'auto';
        $transaction->inventory_id = $inventoryId;
        $transaction->save();

        return $transaction;
    }
}
