<?php

namespace Tests\Feature\Requisition\V2;

use App\Enums\UserType;
use App\Models\InventoryTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesInventoryFixtures;
use Tests\TestCase;

class RequisitionDiscrepancyApiTest extends TestCase
{
    use CreatesInventoryFixtures;
    use RefreshDatabase;

    public function test_it_lists_missing_inventory_location_discrepancies(): void
    {
        $requesterBranch = $this->createBranch('Requester');
        $sourceBranch = $this->createBranch('Source');
        $user = $this->createUser($requesterBranch, UserType::EMPLOYEE->value);
        $product = $this->createProduct(['name' => 'Missing Source Product']);

        $requisition = $this->createRequisition($user, [
            'project_code' => 'DISC-001',
            'status' => 'accepted',
            'issuance_status' => 'incomplete',
        ]);
        $detail = $this->createRequisitionDetail($requisition, $sourceBranch);
        $item = $this->createRequisitionItem($detail, $product, [
            'request_quantity' => 3,
            'full_filled_quantity' => 0,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v2/inventory/requisition-discrepancies?paginate=10')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.requisition_item_id', $item->id)
            ->assertJsonPath('data.0.source_inventory.exists', false)
            ->assertJsonPath('data.0.discrepancy_types.0', 'missing_inventory_location')
            ->assertJsonPath('data.0.discrepancy_labels.0', 'Missing inventory location')
            ->assertJsonPath('data.0.requisition.discrepancy_types.0', 'missing_inventory_location')
            ->assertJsonPath('data.0.requisition.discrepancy_labels.0', 'Missing inventory location');
    }

    public function test_it_lists_issued_quantity_mismatches_and_excludes_clean_rows(): void
    {
        $requesterBranch = $this->createBranch('Requester');
        $sourceBranch = $this->createBranch('Source');
        $user = $this->createUser($requesterBranch, UserType::EMPLOYEE->value);

        $mismatchProduct = $this->createProduct(['name' => 'Mismatch Product']);
        [$mismatchLocation, $mismatchInventory] = $this->seedInventory($mismatchProduct, $sourceBranch, $user, 10);

        $mismatchRequisition = $this->createRequisition($user, [
            'project_code' => 'DISC-002',
            'status' => 'accepted',
            'issuance_status' => 'incomplete',
        ]);
        $mismatchDetail = $this->createRequisitionDetail($mismatchRequisition, $sourceBranch);
        $mismatchItem = $this->createRequisitionItem($mismatchDetail, $mismatchProduct, [
            'request_quantity' => 5,
            'full_filled_quantity' => 5,
        ]);

        $this->createInventoryTransaction(
            $mismatchInventory->id,
            $mismatchProduct->id,
            $sourceBranch->id,
            $user->id,
            'out',
            3,
            $mismatchRequisition->id
        );

        $cleanProduct = $this->createProduct(['name' => 'Clean Product']);
        [, $cleanInventory] = $this->seedInventory($cleanProduct, $sourceBranch, $user, 10);

        $cleanRequisition = $this->createRequisition($user, [
            'project_code' => 'DISC-003',
            'status' => 'completed',
            'issuance_status' => 'completed',
        ]);
        $cleanDetail = $this->createRequisitionDetail($cleanRequisition, $sourceBranch);
        $this->createRequisitionItem($cleanDetail, $cleanProduct, [
            'request_quantity' => 4,
            'full_filled_quantity' => 4,
        ]);

        $this->createInventoryTransaction(
            $cleanInventory->id,
            $cleanProduct->id,
            $sourceBranch->id,
            $user->id,
            'out',
            4,
            $cleanRequisition->id
        );
        $this->createInventoryTransaction(
            $cleanInventory->id,
            $cleanProduct->id,
            $requesterBranch->id,
            $user->id,
            'in',
            4,
            $cleanRequisition->id
        );

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v2/inventory/requisition-discrepancies?paginate=10')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.requisition_item_id', $mismatchItem->id)
            ->assertJsonPath('data.0.quantities.issued_out', 3);

        $this->assertContains('issued_qty_mismatch', $response->json('data.0.discrepancy_types'));
        $this->assertNotContains('DISC-003', array_column($response->json('data'), 'project_code'));
    }

    public function test_it_filters_discrepancies_by_requisition_created_at_range(): void
    {
        $requesterBranch = $this->createBranch('Requester');
        $sourceBranch = $this->createBranch('Source');
        $user = $this->createUser($requesterBranch, UserType::EMPLOYEE->value);
        $product = $this->createProduct(['name' => 'Date Filter Product']);

        $older = $this->createRequisition($user, [
            'project_code' => 'DISC-OLD',
            'status' => 'accepted',
        ]);
        $olderDetail = $this->createRequisitionDetail($older, $sourceBranch);
        $this->createRequisitionItem($olderDetail, $product, [
            'request_quantity' => 1,
            'full_filled_quantity' => 0,
        ]);
        $older->forceFill(['created_at' => '2026-04-01 08:00:00'])->save();

        $newer = $this->createRequisition($user, [
            'project_code' => 'DISC-NEW',
            'status' => 'accepted',
        ]);
        $newerDetail = $this->createRequisitionDetail($newer, $sourceBranch);
        $newerItem = $this->createRequisitionItem($newerDetail, $product, [
            'request_quantity' => 1,
            'full_filled_quantity' => 0,
        ]);
        $newer->forceFill(['created_at' => '2026-04-20 08:00:00'])->save();

        Sanctum::actingAs($user);

        $this->getJson('/api/v2/inventory/requisition-discrepancies?date_from=2026-04-15&date_to=2026-04-25&paginate=10')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.requisition_item_id', $newerItem->id)
            ->assertJsonPath('data.0.requisition.project_code', 'DISC-NEW');
    }

    public function test_it_uses_requested_quantity_as_the_completed_discrepancy_reference(): void
    {
        $requesterBranch = $this->createBranch('Requester');
        $sourceBranch = $this->createBranch('Source');
        $user = $this->createUser($requesterBranch, UserType::EMPLOYEE->value);
        $product = $this->createProduct(['name' => 'Completed Mismatch Product']);

        [$inventoryLocation, $inventory] = $this->seedInventory($product, $sourceBranch, $user, 10);

        $requisition = $this->createRequisition($user, [
            'project_code' => 'DISC-COMPLETE',
            'status' => 'completed',
            'issuance_status' => 'completed',
        ]);
        $detail = $this->createRequisitionDetail($requisition, $sourceBranch);
        $item = $this->createRequisitionItem($detail, $product, [
            'request_quantity' => 5,
            'full_filled_quantity' => 3,
        ]);

        $this->createInventoryTransaction(
            $inventory->id,
            $product->id,
            $sourceBranch->id,
            $user->id,
            'out',
            3,
            $requisition->id
        );

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v2/inventory/requisition-discrepancies?paginate=10')
            ->assertOk()
            ->assertJsonPath('data.0.requisition_item_id', $item->id)
            ->assertJsonMissingPath('data.0.quantities.received_in');

        $this->assertContains('fulfilled_qty_mismatch', $response->json('data.0.discrepancy_types'));
        $this->assertContains('Fulfilled quantity does not match requested quantity', $response->json('data.0.discrepancy_labels'));
    }

    private function createInventoryTransaction(
        int $inventoryId,
        int $productId,
        int $branchId,
        int $userId,
        string $movement,
        int $quantity,
        int $requisitionId
    ): InventoryTransaction {
        $transaction = new InventoryTransaction();
        $transaction->quantity = $quantity;
        $transaction->branch_id = $branchId;
        $transaction->product_id = $productId;
        $transaction->transacted_by_id = $userId;
        $transaction->accepted_by_id = $userId;
        $transaction->movement = $movement;
        $transaction->details = 'requisition discrepancy test';
        $transaction->action = 'auto';
        $transaction->inventory_id = $inventoryId;
        $transaction->from_request_id = $requisitionId;
        $transaction->save();

        return $transaction;
    }
}
