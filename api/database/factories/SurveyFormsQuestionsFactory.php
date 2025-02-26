<?php

namespace Database\Factories;

use App\Models\SurveyFormsQuestions;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SurveyFormsQuestionsFactory extends Factory
{
    protected $model = SurveyFormsQuestions::class;

    public function definition(): array
    {
        

        return [
    'id' => $this->faker->word(),
    'survey_form_id' => \App\Models\SurveyForms::inRandomOrder()->value('id') ?? 1,
    'survey_question_id' => \App\Models\SurveyQuestions::inRandomOrder()->value('id') ?? 1,
    'section' => $this->faker->lexify(str_repeat("?", 250)),
    'name' => $this->faker->lexify(str_repeat("?", 250)),
    'is_mandatory' => $this->faker->numberBetween(1, 1000),
    'is_unique' => $this->faker->numberBetween(1, 1000),
    'order' => $this->faker->numberBetween(1, 1000),
];
    }
}