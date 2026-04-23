<?php

namespace Database\Factories;

use App\Models\QuizAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuizAttemptFactory extends Factory
{
    protected $model = QuizAttempt::class;

    public function definition(): array
    {
        return [
            'activity_id' => $this->faker->randomNumber(),
            'user_id' => fn (array $attributes) => $attributes["user_id"] ?? $this->faker->randomNumber(),
            'status' => 'in_progress',
            'started_at' => now(),
            'expires_at' => now()->addMinutes(30),
            'score' => 0,
        ];
    }
}