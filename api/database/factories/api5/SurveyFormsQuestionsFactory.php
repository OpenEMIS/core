<?php

namespace Database\Factories\Api5;

use App\Models\Api5\SurveyFormsQuestions;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SurveyFormsQuestionsFactory extends Factory
{
    protected $model = SurveyFormsQuestions::class;

    public function definition(): array
    {


        return [
    // 'id' => $this->faker->word(),
    'survey_form_id' => \App\Models\SurveyForms::inRandomOrder()->value('id') ?? \App\Models\SurveyForms::factory()->create()->id,
    'survey_question_id' => \App\Models\SurveyQuestions::inRandomOrder()->value('id') ?? \App\Models\SurveyQuestions::factory()->create()->id,
    'section' => $this->faker->lexify(str_repeat("?", 250)),
    'name' => $this->faker->lexify(str_repeat("?", 250)),
    'is_mandatory' => $this->faker->numberBetween(0, 1),
    'is_unique' => $this->faker->numberBetween(0, 1),
    'order' => $this->faker->numberBetween(1, 1000),
];
    }
}
