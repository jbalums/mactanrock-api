<?php

namespace Database\Factories;

use App\Enums\UserType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'firstname' => fake()->firstName(),
            'lastname' => fake()->lastName(),
            'middlename' => fake()->optional()->firstName() ?? '',
            'contact' => fake()->numerify('09#########'),
            'user_type' => UserType::EMPLOYEE->value,
            'email' => fake()->safeEmail(),
            'username' => fake()->unique()->userName(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
            'branch_id' => null,
            'business_unit' => null,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     *
     * @return static
     */
    public function unverified()
    {
        return $this->state(function (array $attributes) {
            return [
                'email_verified_at' => null,
            ];
        });
    }
}
