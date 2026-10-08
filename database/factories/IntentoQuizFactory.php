<?php

namespace Database\Factories;

use App\Models\IntentoQuiz;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IntentoQuiz>
 */
class IntentoQuizFactory extends Factory
{
    /**
     * Define the model's default state. Requiere indicar `quiz_id`.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $puntaje = fake()->numberBetween(0, 100);

        return [
            'user_id' => User::factory(),
            'puntaje' => $puntaje,
            'aprobado' => $puntaje >= 70,
            'respuestas' => [],
        ];
    }
}
