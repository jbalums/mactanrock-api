<?php

namespace Tests\Feature\Contracts;

use App\Enums\ReceivingStatus;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\Receive;
use App\Models\Requisition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RouteContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_management_uses_plural_resource_routes(): void
    {
        $admin = $this->createUser(UserType::ADMIN);
        $target = $this->createUser(UserType::EMPLOYEE);

        Sanctum::actingAs($admin);

        $this->deleteJson("/api/management/users/{$target->id}")
            ->assertOk();

        $this->deleteJson("/api/management/user/{$target->id}")
            ->assertNotFound();
    }

    public function test_inventory_trigger_updates_use_patch(): void
    {
        Sanctum::actingAs($this->createUser(UserType::ADMIN));

        $this->patchJson('/api/inventory/triggers/999', [])
            ->assertStatus(422);

        $this->postJson('/api/inventory/triggers/999', [])
            ->assertStatus(405);
    }

    public function test_inventory_price_updates_use_patch(): void
    {
        Sanctum::actingAs($this->createUser(UserType::ADMIN));

        $this->patchJson('/api/inventory/price/999', [])
            ->assertStatus(422);

        $this->postJson('/api/inventory/price/999', [])
            ->assertStatus(405);
    }

    public function test_issuance_approval_uses_the_approve_issuance_route(): void
    {
        $branch = $this->createBranch('MAIN-001');
        $approver = $this->createUser(UserType::APPROVING_MANAGER, $branch);

        $requisition = $this->createRequisition($branch, $approver, 'accepted', 'pending');

        Sanctum::actingAs($approver);

        $this->postJson("/api/inventory/approve-issuance/{$requisition->id}")
            ->assertNoContent();

        $this->assertDatabaseHas('requisitions', [
            'id' => $requisition->id,
            'issuance_status' => 'approved',
        ]);

        $this->postJson("/api/inventory/issuance-approve/{$requisition->id}")
            ->assertNotFound();
    }

    public function test_receiving_complete_route_requires_an_id(): void
    {
        $branch = $this->createBranch('REC-001');
        $operator = $this->createUser(UserType::WAREHOUSE_MAN, $branch);

        $receive = new Receive();
        $receive->purchase_order = 'PO-1001';
        $receive->branch_id = $branch->id;
        $receive->date_receive = now()->toDateString();
        $receive->status = ReceivingStatus::Pending;
        $receive->save();

        Sanctum::actingAs($operator);

        $this->patchJson("/api/inventory/receiving-complete/{$receive->id}")
            ->assertOk()
            ->assertJsonPath('id', $receive->id)
            ->assertJsonPath('status', ReceivingStatus::Completed->value);

        $this->assertDatabaseHas('receives', [
            'id' => $receive->id,
            'status' => ReceivingStatus::Completed->value,
        ]);

        $this->patchJson('/api/inventory/receiving-complete')
            ->assertNotFound();
    }

    private function createUser(UserType $type, ?Branch $branch = null): User
    {
        $branch ??= $this->createBranch(strtoupper(fake()->bothify('BR-######')));

        return User::factory()->create([
            'firstname' => fake()->firstName(),
            'lastname' => fake()->lastName(),
            'middlename' => '',
            'contact' => '09123456789',
            'user_type' => $type->value,
            'email' => fake()->unique()->safeEmail(),
            'username' => fake()->unique()->userName(),
            'branch_id' => $branch->id,
        ]);
    }

    private function createBranch(string $code): Branch
    {
        return Branch::query()->create([
            'name' => fake()->company(),
            'address' => fake()->address(),
            'code' => $code,
        ]);
    }

    private function createRequisition(Branch $branch, User $user, string $status, string $issuanceStatus): Requisition
    {
        $requisition = new Requisition();
        $requisition->project_name = 'Contract alignment';
        $requisition->status = $status;
        $requisition->branch_id = $branch->id;
        $requisition->user_id = $user->id;
        $requisition->purpose = 'finished_goods';
        $requisition->account_code = 'ACC-001';
        $requisition->project_code = 'PRJ-001';
        $requisition->issuance_status = $issuanceStatus;
        $requisition->save();

        return $requisition;
    }
}
