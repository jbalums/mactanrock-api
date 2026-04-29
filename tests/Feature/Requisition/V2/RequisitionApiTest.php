<?php

namespace Tests\Feature\Requisition\V2;

use App\Enums\UserType;
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
}
