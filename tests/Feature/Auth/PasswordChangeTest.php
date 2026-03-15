<?php

namespace Tests\Feature\Auth;

use App\Enums\UserType;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_can_change_their_own_password_with_the_correct_old_password(): void
    {
        $user = $this->createUser(UserType::EMPLOYEE, 'employee@example.com', 'employee-user');

        Sanctum::actingAs($user);

        $this->patchJson('/api/management/password', [
            'old_password' => 'password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertNoContent();

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
    }

    public function test_users_cannot_change_their_password_with_an_invalid_old_password(): void
    {
        $user = $this->createUser(UserType::EMPLOYEE, 'employee-invalid@example.com', 'employee-invalid');

        Sanctum::actingAs($user);

        $this->patchJson('/api/management/password', [
            'old_password' => 'wrong-password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['old_password']);

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_admins_can_reset_another_users_password_without_the_old_password(): void
    {
        $admin = $this->createUser(UserType::ADMIN, 'admin@example.com', 'admin-user');
        $target = $this->createUser(UserType::EMPLOYEE, 'target@example.com', 'target-user');

        Sanctum::actingAs($admin);

        $this->patchJson("/api/management/user-password/{$target->id}", [
            'password' => 'reset-password-123',
            'password_confirmation' => 'reset-password-123',
        ])->assertNoContent();

        $this->assertTrue(Hash::check('reset-password-123', $target->fresh()->password));
    }

    public function test_non_admin_users_cannot_reset_another_users_password(): void
    {
        $employee = $this->createUser(UserType::EMPLOYEE, 'employee-reset@example.com', 'employee-reset');
        $target = $this->createUser(UserType::EMPLOYEE, 'target-reset@example.com', 'target-reset');

        Sanctum::actingAs($employee);

        $this->patchJson("/api/management/user-password/{$target->id}", [
            'password' => 'reset-password-123',
            'password_confirmation' => 'reset-password-123',
        ])->assertForbidden();

        $this->assertTrue(Hash::check('password', $target->fresh()->password));
    }

    private function createUser(UserType $type, string $email, string $username): User
    {
        $branch = Branch::query()->create([
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
            'email' => $email,
            'username' => $username,
            'branch_id' => $branch->id,
            'password' => bcrypt('password'),
        ]);
    }
}
