<?php

namespace Tests\Feature\Inventory;

use App\Enums\UserType;
use App\Models\InventoryTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesInventoryFixtures;
use Tests\TestCase;

class ReportBranchFilterTest extends TestCase
{
    use CreatesInventoryFixtures;
    use RefreshDatabase;

    public function test_main_warehouse_admin_can_view_item_costing_across_all_branches_when_no_branch_filter_is_supplied(): void
    {
        ['mainAdmin' => $mainAdmin, 'branchTwo' => $branchTwo] = $this->createReportFixtures();

        Sanctum::actingAs($mainAdmin);

        $response = $this->getJson('/api/inventory/item-costing');

        $response->assertOk();

        $branchIds = collect($response->json('data'))->pluck('branch_id')->unique()->sort()->values()->all();

        $this->assertSame([1, $branchTwo->id], $branchIds);
    }

    public function test_main_warehouse_admin_can_filter_item_costing_to_one_branch(): void
    {
        ['mainAdmin' => $mainAdmin, 'branchTwo' => $branchTwo] = $this->createReportFixtures();

        Sanctum::actingAs($mainAdmin);

        $response = $this->getJson("/api/inventory/item-costing?branch_id={$branchTwo->id}");

        $response->assertOk();

        $branchIds = collect($response->json('data'))->pluck('branch_id')->unique()->values()->all();

        $this->assertSame([$branchTwo->id], $branchIds);
    }

    public function test_branch_users_cannot_escape_branch_scope_on_item_costing(): void
    {
        ['branchUser' => $branchUser] = $this->createReportFixtures();

        Sanctum::actingAs($branchUser);

        $response = $this->getJson('/api/inventory/item-costing?branch_id=1');

        $response->assertOk();

        $branchIds = collect($response->json('data'))->pluck('branch_id')->unique()->values()->all();

        $this->assertSame([$branchUser->branch_id], $branchIds);
    }

    public function test_main_warehouse_admin_can_view_warehouse_issuances_across_all_branches_when_no_branch_filter_is_supplied(): void
    {
        ['mainAdmin' => $mainAdmin, 'branchTwo' => $branchTwo] = $this->createReportFixtures();

        Sanctum::actingAs($mainAdmin);

        $response = $this->getJson('/api/inventory/warehouse-issuances');

        $response->assertOk();

        $branchIds = collect($response->json('data'))->pluck('branch_id')->unique()->sort()->values()->all();

        $this->assertSame([1, $branchTwo->id], $branchIds);
    }

    public function test_main_warehouse_admin_can_filter_warehouse_issuances_to_one_branch(): void
    {
        ['mainAdmin' => $mainAdmin, 'branchTwo' => $branchTwo] = $this->createReportFixtures();

        Sanctum::actingAs($mainAdmin);

        $response = $this->getJson("/api/inventory/warehouse-issuances?branch_id={$branchTwo->id}");

        $response->assertOk();

        $branchIds = collect($response->json('data'))->pluck('branch_id')->unique()->values()->all();

        $this->assertSame([$branchTwo->id], $branchIds);
    }

    public function test_branch_users_cannot_escape_branch_scope_on_warehouse_issuances(): void
    {
        ['branchUser' => $branchUser] = $this->createReportFixtures();

        Sanctum::actingAs($branchUser);

        $response = $this->getJson('/api/inventory/warehouse-issuances?branch_id=1');

        $response->assertOk();

        $branchIds = collect($response->json('data'))->pluck('branch_id')->unique()->values()->all();

        $this->assertSame([$branchUser->branch_id], $branchIds);
    }

    public function test_main_warehouse_admin_can_view_inputs_of_receipts_across_all_branches_when_no_branch_filter_is_supplied(): void
    {
        ['mainAdmin' => $mainAdmin, 'branchTwo' => $branchTwo] = $this->createReportFixtures();

        Sanctum::actingAs($mainAdmin);

        $response = $this->getJson('/api/inventory/inputs-of-receipts');

        $response->assertOk();

        $branchIds = collect($response->json('data'))->pluck('branch_id')->unique()->sort()->values()->all();

        $this->assertSame([1, $branchTwo->id], $branchIds);
    }

    public function test_main_warehouse_admin_can_filter_inputs_of_receipts_to_one_branch(): void
    {
        ['mainAdmin' => $mainAdmin, 'branchTwo' => $branchTwo] = $this->createReportFixtures();

        Sanctum::actingAs($mainAdmin);

        $response = $this->getJson("/api/inventory/inputs-of-receipts?branch_id={$branchTwo->id}");

        $response->assertOk();

        $branchIds = collect($response->json('data'))->pluck('branch_id')->unique()->values()->all();

        $this->assertSame([$branchTwo->id], $branchIds);
    }

    public function test_branch_users_cannot_escape_branch_scope_on_inputs_of_receipts(): void
    {
        ['branchUser' => $branchUser] = $this->createReportFixtures();

        Sanctum::actingAs($branchUser);

        $response = $this->getJson('/api/inventory/inputs-of-receipts?branch_id=1');

        $response->assertOk();

        $branchIds = collect($response->json('data'))->pluck('branch_id')->unique()->values()->all();

        $this->assertSame([$branchUser->branch_id], $branchIds);
    }

    private function createReportFixtures(): array
    {
        $mainWarehouse = $this->createBranch('Main Warehouse');
        $branchTwo = $this->createBranch('Branch Two');

        $mainAdmin = $this->createUser($mainWarehouse, UserType::ADMIN->value, [
            'username' => 'main-admin',
        ]);
        $branchUser = $this->createUser($branchTwo, UserType::EMPLOYEE->value, [
            'username' => 'branch-user',
        ]);

        $productOne = $this->createProduct([
            'name' => 'Main Warehouse Product',
            'code' => 'MW-PROD-001',
            'account_code' => 'ACC-MW-001',
        ]);
        $productTwo = $this->createProduct([
            'name' => 'Branch Two Product',
            'code' => 'BR2-PROD-001',
            'account_code' => 'ACC-BR2-001',
        ]);

        [, $mainInventory] = $this->seedInventory($productOne, $mainWarehouse, $mainAdmin, 10, 100);
        [, $branchInventory] = $this->seedInventory($productTwo, $branchTwo, $branchUser, 12, 200);

        $this->createTransaction(
            $mainInventory->id,
            $productOne->id,
            $mainWarehouse->id,
            $mainAdmin->id,
            'out',
            2,
            'main warehouse issuance'
        );
        $this->createTransaction(
            $branchInventory->id,
            $productTwo->id,
            $branchTwo->id,
            $branchUser->id,
            'out',
            3,
            'branch two issuance'
        );

        $this->createTransaction(
            $mainInventory->id,
            $productOne->id,
            $mainWarehouse->id,
            $mainAdmin->id,
            'in',
            4,
            'main warehouse receipt'
        );
        $this->createTransaction(
            $branchInventory->id,
            $productTwo->id,
            $branchTwo->id,
            $branchUser->id,
            'in',
            5,
            'branch two receipt'
        );

        return compact('mainWarehouse', 'branchTwo', 'mainAdmin', 'branchUser', 'productOne', 'productTwo');
    }

    private function createTransaction(
        int $inventoryId,
        int $productId,
        int $branchId,
        int $userId,
        string $movement,
        int $quantity,
        string $details
    ): InventoryTransaction {
        $transaction = new InventoryTransaction();
        $transaction->inventory_id = $inventoryId;
        $transaction->product_id = $productId;
        $transaction->branch_id = $branchId;
        $transaction->transacted_by_id = $userId;
        $transaction->accepted_by_id = $userId;
        $transaction->movement = $movement;
        $transaction->to_branch_id = $branchId;
        $transaction->from_branch_id = $branchId;
        $transaction->details = $details;
        $transaction->action = 'manual';
        $transaction->quantity = $quantity;
        $transaction->save();

        return $transaction;
    }
}
