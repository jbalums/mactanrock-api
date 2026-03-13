<?php

namespace Tests\Feature\Auth;

use App\Enums\UserType;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_user_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/user')->assertUnauthorized();
    }

    public function test_api_user_endpoint_returns_authenticated_user_and_branch(): void
    {
        $branch = Branch::query()->create([
            'name' => 'Main Warehouse',
            'address' => 'Cebu City',
            'code' => 'MW-000001',
        ]);

        $user = User::factory()->create([
            'firstname' => 'Auth',
            'lastname' => 'User',
            'middlename' => '',
            'contact' => '09123456789',
            'user_type' => UserType::ADMIN->value,
            'email' => 'auth-user@example.com',
            'username' => 'auth-user',
            'branch_id' => $branch->id,
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.username', $user->username)
            ->assertJsonPath('data.branch.id', $branch->id)
            ->assertJsonPath('data.branch.name', $branch->name);
    }
}
