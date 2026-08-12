<?php

namespace Database\Factories;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'phone' => fake()->unique()->numerify('5########'),
            'country_code' => '20',
            'language' => 'en',
            'status' => UserStatus::ONLINE->value,
            'remember_token' => Str::random(10),
        ];
    }
}
