<?php

namespace Database\Factories;

use App\Models\Feedback;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Feedback>
 */
class FeedbackFactory extends Factory
{
    /**
     * Define the model's default state. Requiere indicar `area_id`.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'claridad' => fake()->numberBetween(1, 5),
            'utilidad' => fake()->numberBetween(1, 5),
            'ritmo' => fake()->numberBetween(1, 5),
            'comentario' => fake()->boolean() ? fake()->sentence() : null,
        ];
    }
}
