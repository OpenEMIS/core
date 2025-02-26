<?php

namespace Database\Factories;

use App\Models\InstitutionRepeaterSurveyTableCells;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionRepeaterSurveyTableCellsFactory extends Factory
{
    protected $model = InstitutionRepeaterSurveyTableCells::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $survey_question_id = $this->faker->randomElement(\App\Models\InstitutionRepeaterSurveyTableCells::pluck('survey_question_id')->toArray()) ?? 1;
            $survey_table_column_id = $this->faker->randomElement(\App\Models\InstitutionRepeaterSurveyTableCells::pluck('survey_table_column_id')->toArray()) ?? 1;
            $survey_table_row_id = $this->faker->randomElement(\App\Models\InstitutionRepeaterSurveyTableCells::pluck('survey_table_row_id')->toArray()) ?? 1;
            $institution_repeater_survey_id = $this->faker->randomElement(\App\Models\InstitutionRepeaterSurveyTableCells::pluck('institution_repeater_survey_id')->toArray()) ?? 1;
    $exists = InstitutionRepeaterSurveyTableCells::where('survey_question_id', $survey_question_id)
                ->where('survey_table_column_id', $survey_table_column_id)
                ->where('survey_table_row_id', $survey_table_row_id)
                ->where('institution_repeater_survey_id', $institution_repeater_survey_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'text_value' => $this->faker->lexify(str_repeat("?", 250)),
    'number_value' => $this->faker->numberBetween(1, 1000),
    'decimal_value' => $this->faker->lexify(str_repeat("?", 25)),
    'survey_question_id' => \App\Models\SurveyQuestions::inRandomOrder()->value('id') ?? 1,
    'survey_table_column_id' => \App\Models\SurveyTableColumns::inRandomOrder()->value('id') ?? 1,
    'survey_table_row_id' => \App\Models\SurveyTableRows::inRandomOrder()->value('id') ?? 1,
    'institution_repeater_survey_id' => \App\Models\InstitutionRepeaterSurveys::inRandomOrder()->value('id') ?? 1,
    'modified_user_id' => $this->faker->numberBetween(1, 1000),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 1000),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}