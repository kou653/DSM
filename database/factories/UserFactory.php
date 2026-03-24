<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nom_complet' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'code_acces' => strtoupper(fake()->unique()->bothify('USR###??')),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Keep the method for compatibility with the default Laravel factory API.
     */
    public function unverified(): static
    {
        return $this;
    }
}
