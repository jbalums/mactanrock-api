<?php

namespace Tests\Feature\Receiving;

use App\Enums\ReceivingStatus;
use App\Enums\UserType;
use App\Models\InventoryLocation;
use App\Models\InventoryTransaction;
use App\Models\Receive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesInventoryFixtures;
use Tests\TestCase;

class ReceivingFlowTest extends TestCase
{
    use CreatesInventoryFixtures;
    use RefreshDatabase;

    public function test_completed_receiving_creates_inventory_in_the_receives_branch(): void
    {
        $adminBranch = $this->createBranch('Admin');
        $targetBranch = $this->createBranch('Target');
        $admin = $this->createUser($adminBranch, UserType::ADMIN->value);
        $product = $this->createProduct();

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/inventory/receiving', [
            'purchase_order' => 'PO-100',
            'project_name' => 'Receiving Flow',
            'branch_id' => $targetBranch->id,
            'status' => ReceivingStatus::Completed->value,
            'products' => [$product->id],
            'quantity' => [4],
            'price' => [25],
            'expired_at' => [null],
            'date_receive' => now()->toDateString(),
        ])->assertCreated();

        $receiveId = $response->json('data.id');
        $receive = Receive::query()->findOrFail($receiveId);
        $targetInventoryLocation = InventoryLocation::query()
            ->where('branch_id', $targetBranch->id)
            ->where('product_id', $product->id)
            ->first();

        $this->assertNotNull($targetInventoryLocation);
        $this->assertSame(ReceivingStatus::Completed, $receive->status);
        $this->assertSame(4, (int) $targetInventoryLocation->quantity);
        $this->assertSame(4, (int) $targetInventoryLocation->total_quantity);
        $this->assertDatabaseHas('inventory_transactions', [
            'receive_id' => $receive->id,
            'product_id' => $product->id,
            'movement' => 'in',
            'branch_id' => $targetBranch->id,
            'quantity' => 4,
        ]);
    }

    public function test_pending_receiving_can_be_completed_into_the_receives_branch(): void
    {
        $adminBranch = $this->createBranch('Admin');
        $targetBranch = $this->createBranch('Receiving');
        $admin = $this->createUser($adminBranch, UserType::ADMIN->value);
        $receiver = $this->createUser($targetBranch, UserType::WAREHOUSE_MAN->value);
        $product = $this->createProduct();

        Sanctum::actingAs($admin);

        $storeResponse = $this->postJson('/api/inventory/receiving', [
            'purchase_order' => 'PO-101',
            'project_name' => 'Pending Receiving',
            'branch_id' => $targetBranch->id,
            'status' => ReceivingStatus::Pending->value,
            'products' => [$product->id],
            'quantity' => [6],
            'price' => [30],
            'expired_at' => [null],
            'date_receive' => now()->toDateString(),
        ])->assertCreated();

        $receiveId = $storeResponse->json('data.id');

        $this->assertDatabaseMissing('inventory_transactions', [
            'receive_id' => $receiveId,
            'product_id' => $product->id,
        ]);

        Sanctum::actingAs($receiver);

        $this->patchJson("/api/inventory/receiving-complete/{$receiveId}")
            ->assertOk();

        $receive = Receive::query()->findOrFail($receiveId);
        $targetInventoryLocation = InventoryLocation::query()
            ->where('branch_id', $targetBranch->id)
            ->where('product_id', $product->id)
            ->first();

        $this->assertSame(ReceivingStatus::Completed, $receive->status);
        $this->assertNotNull($targetInventoryLocation);
        $this->assertSame(6, (int) $targetInventoryLocation->quantity);
        $this->assertSame(6, (int) $targetInventoryLocation->total_quantity);
        $this->assertSame(1, InventoryTransaction::query()
            ->where('receive_id', $receiveId)
            ->where('product_id', $product->id)
            ->where('branch_id', $targetBranch->id)
            ->where('movement', 'in')
            ->count());
    }
}
