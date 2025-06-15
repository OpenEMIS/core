<?php

namespace Database\Factories\Api5;

use App\Models\Api5\InstitutionStudentsGpa;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionStudentsGpaFactory extends Factory
{
    protected $model = InstitutionStudentsGpa::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'student_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? \App\Models\AcademicPeriods::factory()->create()->id,
    'education_grade_id' => \App\Models\EducationGrades::inRandomOrder()->value('id') ?? \App\Models\EducationGrades::factory()->create()->id,
    'education_grades_gpa_id' => \App\Models\EducationGradesGpa::inRandomOrder()->value('id') ?? \App\Models\EducationGradesGpa::factory()->create()->id,
    'gpa' => $this->faker->randomFloat(2, 10, 1000),
    'cumulative_gpa' => $this->faker->randomFloat(2, 10, 1000),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
