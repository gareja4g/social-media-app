<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'user_name' => $this->faker->unique()->userName,
            'email' => $this->faker->unique()->safeEmail,
            'profile_picture' => asset("avatar.png"),
            'cover_photo' => asset("cover.png"),
            'bio' => $this->faker->sentence,
            'profile_visibility' => 'public',
            'post_visibility' => 'public',
            'notification_preferences' => json_encode(['email' => true, 'push' => false]),
            'password' => Hash::make('password123'),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
