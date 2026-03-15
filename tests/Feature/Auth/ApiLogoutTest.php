<?php

namespace Tests\Feature\Auth;

use App\Enums\UserType;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class ApiLogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_can_logout_from_the_api(): void
    {
        [, $token] = $this->createAuthenticatedUser();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/logout')
            ->assertNoContent();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_logged_out_tokens_can_no_longer_access_the_api_user_endpoint(): void
    {
        [, $token] = $this->createAuthenticatedUser();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/logout')
            ->assertNoContent();

        $this->assertNull(PersonalAccessToken::findToken($token));

        app('auth')->forgetGuards();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/user')
            ->assertUnauthorized();
    }

    private function createAuthenticatedUser(): array
    {
        $branch = Branch::query()->create([
            'name' => 'Main Warehouse',
            'address' => 'Cebu City',
            'code' => 'MW-000001',
        ]);

        $user = User::factory()->create([
            'firstname' => 'Logout',
            'lastname' => 'Tester',
            'middlename' => '',
            'contact' => '09123456789',
            'user_type' => UserType::ADMIN->value,
            'email' => 'logout-tester@example.com',
            'username' => 'logout-tester',
            'branch_id' => $branch->id,
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        return [$user, $token];
    }
}
