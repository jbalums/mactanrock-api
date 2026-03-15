<?php

namespace Tests\Feature\Requisition;

use App\Enums\UserType;
use App\Models\InventoryLocation;
use App\Models\InventoryTransaction;
use App\Models\Requisition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesInventoryFixtures;
use Tests\TestCase;

class RequisitionFlowTest extends TestCase
{
    use CreatesInventoryFixtures;
    use RefreshDatabase;

    public function test_requisition_can_be_created_approved_accepted_issued_and_received(): void
    {
        $mainBranch = $this->createBranch('Main');
        $requesterBranch = $this->createBranch('Requester');

        $warehouseAdmin = $this->createUser($mainBranch, UserType::ADMIN->value);
        $requester = $this->createUser($requesterBranch, UserType::EMPLOYEE->value);
        $receiver = $this->createUser($requesterBranch, UserType::WAREHOUSE_MAN->value);
        $product = $this->createProduct();

        [$mainInventoryLocation] = $this->seedInventory($product, $mainBranch, $warehouseAdmin, 10);

        Sanctum::actingAs($requester);

        $createResponse = $this->postJson('/api/inventory/requisition', [
            'project_code' => 'PRJ-100',
            'account_code' => 'ACC-100',
            'purpose' => 'project_plant',
            'inventory_id' => [$mainInventoryLocation->id],
            'quantity' => [5],
            'date_needed' => now()->toDateString(),
        ])->assertOk();

        $requisitionId = $createResponse->json('data.id');

        $requisition = Requisition::query()
            ->with('details.items')
            ->findOrFail($requisitionId);

        $this->assertSame('pending', $requisition->status);
        $this->assertSame($requesterBranch->id, (int) $requisition->branch_id);
        $this->assertCount(1, $requisition->details);
        $this->assertSame($mainBranch->id, (int) $requisition->details->first()->location_id);
        $this->assertSame(5, (int) $requisition->details->first()->items->first()->request_quantity);

        Sanctum::actingAs($warehouseAdmin);

        $this->postJson("/api/inventory/requisition-approved/{$requisition->id}")
            ->assertOk();

        $this->assertSame('approved', $requisition->fresh()->status);

        $this->postJson("/api/inventory/requisition-accept/{$requisition->id}")
            ->assertNoContent();

        $requisition = $requisition->fresh(['details.items']);
        $detail = $requisition->details->first();
        $item = $detail->items->first();

        $this->assertSame('accepted', $requisition->status);
        $this->assertSame('pending', $requisition->issuance_status);

        $this->postJson("/api/inventory/issuances/{$requisition->id}", [
            'issued_qty' => [
                $detail->id => [
                    $item->id => 5,
                ],
            ],
        ])->assertOk();

        $requisition = $requisition->fresh();

        $this->assertSame('accepted', $requisition->status);
        $this->assertSame('completed', $requisition->issuance_status);
        $this->assertSame(5, (int) $mainInventoryLocation->fresh()->quantity);
        $this->assertSame(5, (int) $mainInventoryLocation->fresh()->total_quantity);
        $this->assertDatabaseHas('inventory_transactions', [
            'from_request_id' => $requisition->id,
            'product_id' => $product->id,
            'movement' => 'out',
            'branch_id' => $mainBranch->id,
            'to_branch_id' => $requesterBranch->id,
            'quantity' => 5,
        ]);

        Sanctum::actingAs($receiver);

        $this->postJson("/api/inventory/issuances-recieved/{$requisition->id}", [
            'received_qty' => [
                $detail->id => [
                    $item->id => 5,
                ],
            ],
        ])->assertOk();

        $requisition = $requisition->fresh();
        $receiverInventoryLocation = InventoryLocation::query()
            ->where('branch_id', $requesterBranch->id)
            ->where('product_id', $product->id)
            ->first();

        $this->assertNotNull($receiverInventoryLocation);
        $this->assertSame('completed', $requisition->status);
        $this->assertSame('completed', $requisition->issuance_status);
        $this->assertSame(5, (int) $receiverInventoryLocation->quantity);
        $this->assertSame(5, (int) $receiverInventoryLocation->total_quantity);
        $this->assertDatabaseHas('inventory_transactions', [
            'from_request_id' => $requisition->id,
            'product_id' => $product->id,
            'movement' => 'in',
            'branch_id' => $requesterBranch->id,
            'from_branch_id' => 1,
            'to_branch_id' => $requesterBranch->id,
            'quantity' => 5,
        ]);

        $this->assertSame(2, InventoryTransaction::query()
            ->where('from_request_id', $requisition->id)
            ->count());
    }
}
