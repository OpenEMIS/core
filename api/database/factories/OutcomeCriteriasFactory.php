<?php

namespace Database\Factories;

use App\Models\OutcomeCriterias;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class OutcomeCriteriasFactory extends Factory
{
    protected $model = OutcomeCriterias::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $id = $this->faker->randomElement(\App\Models\OutcomeCriterias::pluck('id')->toArray()) ?? 1;
            $academic_period_id = $this->faker->randomElement(\App\Models\OutcomeCriterias::pluck('academic_period_id')->toArray()) ?? 1;
            $outcome_template_id = $this->faker->randomElement(\App\Models\OutcomeCriterias::pluck('outcome_template_id')->toArray()) ?? 1;
            $education_grade_id = $this->faker->randomElement(\App\Models\OutcomeCriterias::pluck('education_grade_id')->toArray()) ?? 1;
            $education_subject_id = $this->faker->randomElement(\App\Models\OutcomeCriterias::pluck('education_subject_id')->toArray()) ?? 1;
    $exists = OutcomeCriterias::where('id', $id)
                ->where('academic_period_id', $academic_period_id)
                ->where('outcome_template_id', $outcome_template_id)
                ->where('education_grade_id', $education_grade_id)
                ->where('education_subject_id', $education_subject_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'id' => $this->model::max('id') + 1,
    'code' => $this->faker->lexify(str_repeat("?", 20)),
    'name' => $this->faker->lexify(str_repeat("?", 1500)),
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? \App\Models\AcademicPeriods::factory()->create()->id,
    'outcome_template_id' => \App\Models\OutcomeTemplates::inRandomOrder()->value('id') ?? \App\Models\OutcomeTemplates::factory()->create()->id,
    'education_grade_id' => \App\Models\EducationGrades::inRandomOrder()->value('id') ?? \App\Models\EducationGrades::factory()->create()->id,
    'education_subject_id' => \App\Models\EducationSubjects::inRandomOrder()->value('id') ?? \App\Models\EducationSubjects::factory()->create()->id,
    'outcome_grading_type_id' => \App\Models\OutcomeGradingTypes::inRandomOrder()->value('id') ?? \App\Models\OutcomeGradingTypes::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
