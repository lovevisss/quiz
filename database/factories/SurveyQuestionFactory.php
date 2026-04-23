<?php

namespace Database\Factories;

use App\Models\SurveyQuestion;
use App\Models\SurveyTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class SurveyQuestionFactory extends Factory
{
    protected $model = SurveyQuestion::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(['single', 'multiple', 'text']);
        $options = $type === 'text' ? [] : ['A', 'B', 'C', 'D'];
        return [
            'survey_template_id' => SurveyTemplate::factory(),
            'content' => $this->faker->sentence(),
            'type' => $type,
            'options' => $options,
            'required' => $this->faker->boolean(70),
            'order' => $this->faker->numberBetween(1, 10),
        ];
    }
}
