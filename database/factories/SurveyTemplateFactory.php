<?php

namespace Database\Factories;

use App\Models\SurveyTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class SurveyTemplateFactory extends Factory
{
    protected $model = SurveyTemplate::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(3, true),
            'description' => $this->faker->sentence(),
            'status' => true,
        ];
    }
}
