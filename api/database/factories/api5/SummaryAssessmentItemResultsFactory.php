<?php

namespace Database\Factories\Api5;

use App\Models\Api5\SummaryAssessmentItemResults;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SummaryAssessmentItemResultsFactory extends Factory
{
    protected $model = SummaryAssessmentItemResults::class;

    public function definition(): array
    {


        return [
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? \App\Models\AcademicPeriods::factory()->create()->id,
    'academic_period_name' => $this->faker->lexify(str_repeat("?", 150)),
    'assessment_id' => \App\Models\Assessments::inRandomOrder()->value('id') ?? \App\Models\Assessments::factory()->create()->id,
    'assessment_code' => $this->faker->lexify(str_repeat("?", 150)),
    'assessment_name' => $this->faker->lexify(str_repeat("?", 150)),
    'assessment_period_id' => \App\Models\AssessmentPeriods::inRandomOrder()->value('id') ?? \App\Models\AssessmentPeriods::factory()->create()->id,
    'assessment_period_name' => $this->faker->lexify(str_repeat("?", 150)),
    'academic_term' => $this->faker->lexify(str_repeat("?", 150)),
    'subject_id' => $this->faker->numberBetween(1, 1000),
    'subject_name' => $this->faker->lexify(str_repeat("?", 150)),
    'education_grade_id' => \App\Models\EducationGrades::inRandomOrder()->value('id') ?? \App\Models\EducationGrades::factory()->create()->id,
    'education_grade' => $this->faker->lexify(str_repeat("?", 150)),
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'institution_code' => $this->faker->lexify(str_repeat("?", 150)),
    'institution_name' => $this->faker->lexify(str_repeat("?", 150)),
    'institution_provider_id' => \App\Models\InstitutionProviders::inRandomOrder()->value('id') ?? \App\Models\InstitutionProviders::factory()->create()->id,
    'institution_provider' => $this->faker->lexify(str_repeat("?", 150)),
    'area_id' => \App\Models\Areas::inRandomOrder()->value('id') ?? \App\Models\Areas::factory()->create()->id,
    'area_name' => $this->faker->lexify(str_repeat("?", 150)),
    'institution_class_id' => $this->faker->numberBetween(1, 1000),
    'institution_class_name' => $this->faker->lexify(str_repeat("?", 150)),
    'count_students' => $this->faker->numberBetween(1, 1000),
    'count_marked_students' => $this->faker->numberBetween(1, 1000),
    'missing_marks' => $this->faker->numberBetween(1, 1000),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
