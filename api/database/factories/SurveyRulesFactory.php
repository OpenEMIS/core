<?php

namespace Database\Factories;

use App\Models\SurveyRules;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SurveyRulesFactory extends Factory
{
    protected $model = SurveyRules::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $survey_form_id = $this->faker->randomElement(\App\Models\SurveyRules::pluck('survey_form_id')->toArray()) ?? 1;
            $survey_question_id = $this->faker->randomElement(\App\Models\SurveyRules::pluck('survey_question_id')->toArray()) ?? 1;
    $exists = SurveyRules::where('survey_form_id', $survey_form_id)
                ->where('survey_question_id', $survey_question_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'survey_form_id' => \App\Models\SurveyForms::inRandomOrder()->value('id') ?? 1,
    'survey_question_id' => \App\Models\SurveyQuestions::inRandomOrder()->value('id') ?? 1,
    'dependent_question_id' => $this->faker->numberBetween(1, 1000),
    'show_options' => $this->faker->text(50),
    'enabled' => $this->faker->numberBetween(1, 1000),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'modified_user_id' => $this->faker->numberBetween(1, 1000),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 1000),
];
    }
}