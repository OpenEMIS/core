<?php

namespace Database\Factories;

use App\Models\InstitutionStudentSurveyTableCells;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionStudentSurveyTableCellsFactory extends Factory
{
    protected $model = InstitutionStudentSurveyTableCells::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $survey_question_id = $this->faker->randomElement(\App\Models\InstitutionStudentSurveyTableCells::pluck('survey_question_id')->toArray()) ?? 1;
            $survey_table_column_id = $this->faker->randomElement(\App\Models\InstitutionStudentSurveyTableCells::pluck('survey_table_column_id')->toArray()) ?? 1;
            $survey_table_row_id = $this->faker->randomElement(\App\Models\InstitutionStudentSurveyTableCells::pluck('survey_table_row_id')->toArray()) ?? 1;
            $institution_student_survey_id = $this->faker->randomElement(\App\Models\InstitutionStudentSurveyTableCells::pluck('institution_student_survey_id')->toArray()) ?? 1;
    $exists = InstitutionStudentSurveyTableCells::where('survey_question_id', $survey_question_id)
                ->where('survey_table_column_id', $survey_table_column_id)
                ->where('survey_table_row_id', $survey_table_row_id)
                ->where('institution_student_survey_id', $institution_student_survey_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'text_value' => $this->faker->lexify(str_repeat("?", 250)),
    'number_value' => $this->faker->numberBetween(1, 1000),
    'decimal_value' => $this->faker->lexify(str_repeat("?", 25)),
    'survey_question_id' => \App\Models\SurveyQuestions::inRandomOrder()->value('id') ?? \App\Models\SurveyQuestions::factory()->create()->id,
    'survey_table_column_id' => \App\Models\SurveyTableColumns::inRandomOrder()->value('id') ?? \App\Models\SurveyTableColumns::factory()->create()->id,
    'survey_table_row_id' => \App\Models\SurveyTableRows::inRandomOrder()->value('id') ?? \App\Models\SurveyTableRows::factory()->create()->id,
    'institution_student_survey_id' => \App\Models\InstitutionStudentSurveys::inRandomOrder()->value('id') ?? \App\Models\InstitutionStudentSurveys::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
