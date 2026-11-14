<?php

namespace Database\Factories;

use App\Support\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '09'.fake()->unique()->numerify('########'),
            'password' => static::$password ??= Hash::make('password'),
            'full_name' => fake()->name(),
            'role' => UserRole::STUDENT,
            'status' => 'active',
            'remember_token' => Str::random(10),
        ];
    }
}
