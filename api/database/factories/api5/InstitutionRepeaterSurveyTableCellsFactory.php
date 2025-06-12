<?php

namespace Database\Factories\Api5;

use App\Models\Api5\InstitutionRepeaterSurveyTableCells;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionRepeaterSurveyTableCellsFactory extends Factory
{
    protected $model = InstitutionRepeaterSurveyTableCells::class;

    public function definition(): array
    {

        return [
    'text_value' => $this->faker->lexify(str_repeat("?", 250)),
    'number_value' => $this->faker->numberBetween(1, 1000),
    'decimal_value' => $this->faker->lexify(str_repeat("?", 25)),
    'survey_question_id' => \App\Models\SurveyQuestions::inRandomOrder()->value('id') ?? \App\Models\SurveyQuestions::factory()->create()->id,
    'survey_table_column_id' => \App\Models\SurveyTableColumns::inRandomOrder()->value('id') ?? \App\Models\SurveyTableColumns::factory()->create()->id,
    'survey_table_row_id' => \App\Models\SurveyTableRows::inRandomOrder()->value('id') ?? \App\Models\SurveyTableRows::factory()->create()->id,
    'institution_repeater_survey_id' => \App\Models\InstitutionRepeaterSurveys::inRandomOrder()->value('id') ?? \App\Models\InstitutionRepeaterSurveys::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
