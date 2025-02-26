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
        $attempts = 0;
do {
    $student_id = $this->faker->randomElement(\App\Models\InstitutionOutcomeResults::pluck('student_id')->toArray()) ?? 1;
            $outcome_template_id = $this->faker->randomElement(\App\Models\InstitutionOutcomeResults::pluck('outcome_template_id')->toArray()) ?? 1;
            $outcome_period_id = $this->faker->randomElement(\App\Models\InstitutionOutcomeResults::pluck('outcome_period_id')->toArray()) ?? 1;
            $education_grade_id = $this->faker->randomElement(\App\Models\InstitutionOutcomeResults::pluck('education_grade_id')->toArray()) ?? 1;
            $education_subject_id = $this->faker->randomElement(\App\Models\InstitutionOutcomeResults::pluck('education_subject_id')->toArray()) ?? 1;
            $outcome_criteria_id = $this->faker->randomElement(\App\Models\InstitutionOutcomeResults::pluck('outcome_criteria_id')->toArray()) ?? 1;
            $institution_id = $this->faker->randomElement(\App\Models\InstitutionOutcomeResults::pluck('institution_id')->toArray()) ?? 1;
            $academic_period_id = $this->faker->randomElement(\App\Models\InstitutionOutcomeResults::pluck('academic_period_id')->toArray()) ?? 1;
    $exists = InstitutionOutcomeResults::where('student_id', $student_id)
                ->where('outcome_template_id', $outcome_template_id)
                ->where('outcome_period_id', $outcome_period_id)
                ->where('education_grade_id', $education_grade_id)
                ->where('education_subject_id', $education_subject_id)
                ->where('outcome_criteria_id', $outcome_criteria_id)
                ->where('institution_id', $institution_id)
                ->where('academic_period_id', $academic_period_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'outcome_grading_option_id' => \App\Models\OutcomeGradingOptions::inRandomOrder()->value('id') ?? 1,
    'student_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? 1,
    'outcome_template_id' => \App\Models\OutcomeTemplates::inRandomOrder()->value('id') ?? 1,
    'outcome_period_id' => \App\Models\OutcomePeriods::inRandomOrder()->value('id') ?? 1,
    'education_grade_id' => \App\Models\EducationGrades::inRandomOrder()->value('id') ?? 1,
    'education_subject_id' => \App\Models\EducationSubjects::inRandomOrder()->value('id') ?? 1,
    'outcome_criteria_id' => \App\Models\OutcomeCriterias::inRandomOrder()->value('id') ?? 1,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? 1,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? 1,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
