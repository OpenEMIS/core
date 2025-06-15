<?php

namespace Database\Factories\Api5;

use App\Models\Api5\SurveyRules;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SurveyRulesFactory extends Factory
{
    protected $model = SurveyRules::class;

    public function definition(): array
    {

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'survey_form_id' =>  \App\Models\SurveyForms::factory()->create()->id,
    'survey_question_id' =>  \App\Models\SurveyQuestions::factory()->create()->id,
    'dependent_question_id' => $this->faker->numberBetween(1, 1000),
    'show_options' => $this->faker->text(50),
    'enabled' => $this->faker->numberBetween(1, 1000),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
];
    }
}
