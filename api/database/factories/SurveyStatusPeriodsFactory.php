<?php

namespace Database\Factories;

use App\Models\SurveyStatusPeriods;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SurveyStatusPeriodsFactory extends Factory
{
    protected $model = SurveyStatusPeriods::class;

    public function definition(): array
    {


        return [
    // 'id' => $this->faker->word(),
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? \App\Models\AcademicPeriods::factory()->create()->id,
    'survey_status_id' => \App\Models\SurveyStatuses::inRandomOrder()->value('id') ?? \App\Models\SurveyStatuses::factory()->create()->id,
];
    }
}
