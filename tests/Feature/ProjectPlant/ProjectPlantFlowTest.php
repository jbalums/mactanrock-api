<?php

namespace Tests\Feature\ProjectPlant;

use App\Enums\UserType;
use App\Models\InventoryLocation;
use App\Models\InventoryTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesInventoryFixtures;
use Tests\TestCase;

class ProjectPlantFlowTest extends TestCase
{
    use CreatesInventoryFixtures;
    use RefreshDatabase;

    public function test_project_plant_items_can_be_consumed(): void
    {
        $mainBranch = $this->createBranch('Main');
        $projectBranch = $this->createBranch('Project');
        $mainAdmin = $this->createUser($mainBranch, UserType::ADMIN->value);
        $projectWarehouse = $this->createUser($projectBranch, UserType::WAREHOUSE_MAN->value);
        $product = $this->createProduct();

        [$projectInventoryLocation] = $this->seedInventory($product, $projectBranch, $projectWarehouse, 5);

        $requisition = $this->createRequisition($projectWarehouse, [
            'status' => 'completed',
            'accepted_by_id' => $mainAdmin->id,
            'purpose' => 'project_plant',
        ]);
        $detail = $this->createRequisitionDetail($requisition, $mainBranch, ['status' => 'completed']);
        $item = $this->createRequisitionItem($detail, $product, [
            'request_quantity' => 5,
            'full_filled_quantity' => 5,
            'status' => 'completed',
        ]);

        Sanctum::actingAs($projectWarehouse);

        $this->postJson('/api/inventory/consume-items', [
            'requisition_items_id' => [$item->id],
            'product_id' => [$product->id],
            'qty' => [2],
        ])->assertOk();

        $this->assertSame(2, (int) $item->fresh()->used_qty);
        $this->assertSame(3, (int) $projectInventoryLocation->fresh()->quantity);
        $this->assertSame(3, (int) $projectInventoryLocation->fresh()->total_quantity);
        $this->assertDatabaseHas('inventory_transactions', [
            'product_id' => $product->id,
            'branch_id' => $projectBranch->id,
            'movement' => 'out',
            'quantity' => 2,
            'details' => 'used/consumed items',
        ]);
    }

    public function test_project_plant_items_can_be_returned_to_main_warehouse(): void
    {
        $mainBranch = $this->createBranch('Main');
        $projectBranch = $this->createBranch('Project');
        $mainAdmin = $this->createUser($mainBranch, UserType::ADMIN->value);
        $projectWarehouse = $this->createUser($projectBranch, UserType::WAREHOUSE_MAN->value);
        $product = $this->createProduct();

        [$mainInventoryLocation] = $this->seedInventory($product, $mainBranch, $mainAdmin, 0);
        [$projectInventoryLocation] = $this->seedInventory($product, $projectBranch, $projectWarehouse, 5);

        $requisition = $this->createRequisition($projectWarehouse, [
            'status' => 'completed',
            'accepted_by_id' => $mainAdmin->id,
            'purpose' => 'project_plant',
        ]);
        $detail = $this->createRequisitionDetail($requisition, $mainBranch, ['status' => 'completed']);
        $item = $this->createRequisitionItem($detail, $product, [
            'request_quantity' => 5,
            'full_filled_quantity' => 5,
            'status' => 'completed',
        ]);

        Sanctum::actingAs($projectWarehouse);

        $this->postJson('/api/inventory/return-items', [
            'requisition_items_id' => [$item->id],
            'product_id' => [$product->id],
            'qty' => [2],
        ])->assertOk();

        $this->assertSame(2, (int) $item->fresh()->returned_qty);
        $this->assertSame(3, (int) $projectInventoryLocation->fresh()->quantity);
        $this->assertSame(3, (int) $projectInventoryLocation->fresh()->total_quantity);
        $this->assertSame(2, (int) $mainInventoryLocation->fresh()->quantity);
        $this->assertSame(2, (int) $mainInventoryLocation->fresh()->total_quantity);
        $this->assertSame(2, InventoryTransaction::query()
            ->where('product_id', $product->id)
            ->whereIn('details', ['return materials to main warehouse', 'materials returned by warehouse'])
            ->count());
    }
}
