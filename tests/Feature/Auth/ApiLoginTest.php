<?php

namespace Tests\Feature\Auth;

use App\Enums\UserType;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class ApiLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_can_authenticate_with_valid_api_credentials(): void
    {
        $user = $this->createApiUser();

        $response = $this->postJson('/api/login', [
            'username' => $user->username,
            'password' => 'password',
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'access_token',
                'user' => [
                    'id',
                    'firstname',
                    'lastname',
                    'email',
                    'username',
                    'branch_id',
                ],
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_users_cannot_authenticate_with_invalid_api_credentials(): void
    {
        $user = $this->createApiUser();

        $response = $this->postJson('/api/login', [
            'username' => $user->username,
            'password' => 'wrong-password',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['username']);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    private function createApiUser(): User
    {
        $branch = Branch::query()->create([
            'name' => 'Main Warehouse',
            'address' => 'Cebu City',
            'code' => 'MW-000001',
        ]);

        return User::factory()->create([
            'firstname' => 'API',
            'lastname' => 'Tester',
            'middlename' => '',
            'contact' => '09123456789',
            'user_type' => UserType::ADMIN->value,
            'email' => 'api-tester@example.com',
            'username' => 'api-tester',
            'branch_id' => $branch->id,
            'password' => Hash::make('password'),
        ]);
    }
}
