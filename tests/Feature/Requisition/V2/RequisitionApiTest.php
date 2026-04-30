<?php

namespace Tests\Feature\Requisition\V2;

use App\Enums\UserType;
use App\Models\InventoryTransaction;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesInventoryFixtures;
use Tests\TestCase;

class RequisitionApiTest extends TestCase
{
    use CreatesInventoryFixtures;
    use RefreshDatabase;

    public function test_v2_requisition_list_filters_by_created_at_range(): void
    {
        $branch = $this->createBranch('Req Branch');
        $user = $this->createUser($branch, UserType::EMPLOYEE->value);

        $older = $this->createRequisition($user, [
            'project_name' => 'Old Request',
            'project_code' => 'OLD-001',
            'needed_at' => '2026-04-01',
        ]);
        $middle = $this->createRequisition($user, [
            'project_name' => 'Middle Request',
            'project_code' => 'MID-001',
            'needed_at' => '2026-04-10',
        ]);
        $newer = $this->createRequisition($user, [
            'project_name' => 'New Request',
            'project_code' => 'NEW-001',
            'needed_at' => '2026-04-20',
        ]);

        $older->forceFill(['created_at' => Carbon::parse('2026-04-01 08:00:00')])->save();
        $middle->forceFill(['created_at' => Carbon::parse('2026-04-15 08:00:00')])->save();
        $newer->forceFill(['created_at' => Carbon::parse('2026-04-25 08:00:00')])->save();

        Sanctum::actingAs($user);

        $this->getJson('/api/v2/inventory/requisition?date_from=2026-04-10&date_to=2026-04-20&paginate=10')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $middle->id)
            ->assertJsonPath('data.0.project_code', 'MID-001');
    }

    public function test_v2_requisition_list_keeps_branch_scope_without_branch_override(): void
    {
        $branch = $this->createBranch('Scoped Branch');
        $otherBranch = $this->createBranch('Other Branch');
        $user = $this->createUser($branch, UserType::EMPLOYEE->value);

        $scoped = $this->createRequisition($user, [
            'project_name' => 'Scoped Request',
            'project_code' => 'SCP-001',
        ]);
        $otherUser = $this->createUser($otherBranch, UserType::EMPLOYEE->value);
        $this->createRequisition($otherUser, [
            'project_name' => 'Other Request',
            'project_code' => 'OTH-001',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v2/inventory/requisition?paginate=10')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $scoped->id)
            ->assertJsonPath('data.0.project_code', 'SCP-001');
    }

    public function test_v2_requisition_list_shows_cross_branch_approved_requests_for_main_approval_roles(): void
    {
        $mainBranch = $this->createBranch('Main Branch');
        $requesterBranch = $this->createBranch('Requester Branch');
        $approver = $this->createUser($mainBranch, UserType::WAREHOUSE_MAN->value);
        $requester = $this->createUser($requesterBranch, UserType::EMPLOYEE->value);

        $approved = $this->createRequisition($requester, [
            'project_name' => 'Approved Request',
            'project_code' => 'APR-001',
            'status' => 'approved',
        ]);
        $pending = $this->createRequisition($requester, [
            'project_name' => 'Pending Request',
            'project_code' => 'PND-001',
            'status' => 'pending',
        ]);

        Sanctum::actingAs($approver);

        $this->getJson('/api/v2/inventory/requisition?type=approved&paginate=10')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $approved->id)
            ->assertJsonPath('data.0.project_code', 'APR-001');
    }

    public function test_v2_requisition_list_includes_has_inventory_transactions_flag(): void
    {
        $branch = $this->createBranch('Txn Branch');
        $user = $this->createUser($branch, UserType::EMPLOYEE->value);

        $withTransactions = $this->createRequisition($user, [
            'project_name' => 'With Transactions',
            'project_code' => 'TXN-001',
        ]);
        $withoutTransactions = $this->createRequisition($user, [
            'project_name' => 'Without Transactions',
            'project_code' => 'TXN-002',
        ]);

        $transaction = new InventoryTransaction();
        $transaction->quantity = 5;
        $transaction->branch_id = $branch->id;
        $transaction->product_id = $this->createProduct()->id;
        $transaction->transacted_by_id = $user->id;
        $transaction->accepted_by_id = $user->id;
        $transaction->movement = 'out';
        $transaction->details = 'requisition transaction';
        $transaction->action = 'auto';
        $transaction->from_request_id = $withTransactions->id;
        $transaction->inventory_id = $this->seedInventory(
            $this->createProduct(['name' => 'Txn Product']),
            $branch,
            $user,
            5
        )[1]->id;
        $transaction->save();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v2/inventory/requisition?paginate=10')
            ->assertOk();

        $data = collect($response->json('data'))->keyBy('id');

        $this->assertTrue($data[$withTransactions->id]['has_inventory_transactions']);
        $this->assertFalse($data[$withoutTransactions->id]['has_inventory_transactions']);
    }
}
