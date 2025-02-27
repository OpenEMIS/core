<?php

namespace Database\Factories;

use App\Models\InstitutionOutcomeResults;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionOutcomeResultsFactory extends Factory
{
    protected $model = InstitutionOutcomeResults::class;

    public function definition(): array
    {

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'outcome_grading_option_id' => \App\Models\OutcomeGradingOptions::inRandomOrder()->value('id') ?? \App\Models\OutcomeGradingOptions::factory()->create()->id,
    'student_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'outcome_template_id' => \App\Models\OutcomeTemplates::inRandomOrder()->value('id') ?? \App\Models\OutcomeTemplates::factory()->create()->id,
    'outcome_period_id' => \App\Models\OutcomePeriods::inRandomOrder()->value('id') ?? \App\Models\OutcomePeriods::factory()->create()->id,
    'education_grade_id' => \App\Models\EducationGrades::inRandomOrder()->value('id') ?? \App\Models\EducationGrades::factory()->create()->id,
    'education_subject_id' => \App\Models\EducationSubjects::inRandomOrder()->value('id') ?? \App\Models\EducationSubjects::factory()->create()->id,
    'outcome_criteria_id' => \App\Models\OutcomeCriterias::inRandomOrder()->value('id') ?? \App\Models\OutcomeCriterias::factory()->create()->id,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? \App\Models\AcademicPeriods::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
