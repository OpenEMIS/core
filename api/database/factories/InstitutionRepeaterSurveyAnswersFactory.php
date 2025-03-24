<?php

namespace Database\Factories;

use App\Models\InstitutionRepeaterSurveyAnswers;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionRepeaterSurveyAnswersFactory extends Factory
{
    protected $model = InstitutionRepeaterSurveyAnswers::class;

    public function definition(): array
    {


        return [
    // 'id' => $this->faker->word(),
    'text_value' => $this->faker->lexify(str_repeat("?", 250)),
    'number_value' => $this->faker->lexify(str_repeat("?", 255)),
    'decimal_value' => $this->faker->lexify(str_repeat("?", 25)),
    'textarea_value' => $this->faker->text(50),
    'date_value' => \Carbon\Carbon::now()->format("Y-m-d"),
    'time_value' => $this->faker->word(),
    'file' => $this->faker->word(),
    'survey_question_id' => \App\Models\SurveyQuestions::inRandomOrder()->value('id') ?? \App\Models\SurveyQuestions::factory()->create()->id,
    'institution_repeater_survey_id' => \App\Models\InstitutionRepeaterSurveys::inRandomOrder()->value('id') ?? \App\Models\InstitutionRepeaterSurveys::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
