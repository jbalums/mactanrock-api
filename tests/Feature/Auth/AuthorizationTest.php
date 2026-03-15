<?php

namespace Tests\Feature\Auth;

use App\Enums\UserType;
use App\Models\Branch;
use App\Models\Requisition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_management_routes(): void
    {
        Sanctum::actingAs($this->createUser(UserType::ADMIN));

        $this->getJson('/api/management/users')
            ->assertOk();
    }

    public function test_non_admin_cannot_access_management_routes(): void
    {
        Sanctum::actingAs($this->createUser(UserType::EMPLOYEE));

        $this->getJson('/api/management/users')
            ->assertForbidden();
    }

    public function test_employee_can_create_requisitions_but_cannot_change_inventory_controls(): void
    {
        Sanctum::actingAs($this->createUser(UserType::EMPLOYEE));

        $this->postJson('/api/inventory/requisition', [])
            ->assertStatus(422);

        $this->patchJson('/api/inventory/triggers/999', [])
            ->assertForbidden();
    }

    public function test_operational_roles_can_access_receiving_mutations(): void
    {
        Sanctum::actingAs($this->createUser(UserType::WAREHOUSE_MAN));

        $this->postJson('/api/inventory/receiving', [])
            ->assertStatus(422);
    }

    public function test_employees_cannot_access_receiving_mutations(): void
    {
        Sanctum::actingAs($this->createUser(UserType::EMPLOYEE));

        $this->postJson('/api/inventory/receiving', [])
            ->assertForbidden();
    }

    public function test_approving_managers_can_approve_requisitions(): void
    {
        $branch = Branch::query()->create([
            'name' => 'Main Warehouse',
            'address' => 'Cebu City',
            'code' => 'MW-000001',
        ]);

        $approver = $this->createUser(UserType::APPROVING_MANAGER, $branch);

        $requisition = $this->createPendingRequisition($branch, $approver);

        Sanctum::actingAs($approver);

        $this->postJson("/api/inventory/requisition-approved/{$requisition->id}")
            ->assertOk()
            ->assertJsonPath('requisition.id', $requisition->id)
            ->assertJsonPath('requisition.status', 'approved');

        $this->assertDatabaseHas('requisitions', [
            'id' => $requisition->id,
            'status' => 'approved',
            'accepted_by_id' => $approver->id,
        ]);
    }

    public function test_employees_cannot_approve_requisitions(): void
    {
        $branch = Branch::query()->create([
            'name' => 'Main Warehouse',
            'address' => 'Cebu City',
            'code' => 'MW-000001',
        ]);

        $employee = $this->createUser(UserType::EMPLOYEE, $branch);

        $requisition = $this->createPendingRequisition($branch, $employee);

        Sanctum::actingAs($employee);

        $this->postJson("/api/inventory/requisition-approved/{$requisition->id}")
            ->assertForbidden();
    }

    private function createUser(UserType $type, ?Branch $branch = null): User
    {
        $branch ??= Branch::query()->create([
            'name' => fake()->company(),
            'address' => fake()->address(),
            'code' => strtoupper(fake()->bothify('BR-######')),
        ]);

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

    private function createPendingRequisition(Branch $branch, User $user): Requisition
    {
        $requisition = new Requisition();
        $requisition->project_name = 'Plant expansion';
        $requisition->status = 'pending';
        $requisition->branch_id = $branch->id;
        $requisition->user_id = $user->id;
        $requisition->purpose = 'finished_goods';
        $requisition->account_code = 'ACC-001';
        $requisition->project_code = 'PRJ-001';
        $requisition->save();

        return $requisition;
    }
}
