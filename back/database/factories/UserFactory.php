<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
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
//        $table->id();
//        $table->string('name')->nullable();
//        $table->string('username')->nullable();
//        $table->string('role')->default('Usuario');
//        $table->string('avatar')->default('default.png');
//        $table->string('email')->nullable();
//        $table->timestamp('email_verified_at')->nullable();
//        $table->string('password');
//        $table->rememberToken();
//        $table->softDeletes();
//        $table->timestamps();
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'username' => fake()->unique()->userName(),
            'role' => 'Usuario',
            'avatar' => 'default.png',
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
