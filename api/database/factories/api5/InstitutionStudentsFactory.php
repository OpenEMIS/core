<?php

namespace Database\Factories\Api5;

use App\Models\Api5\InstitutionStudents;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionStudentsFactory extends Factory
{
    protected $model = InstitutionStudents::class;

    public function definition(): array
    {


        return [
    // 'id' => $this->faker->word(),
    'student_status_id' => \App\Models\StudentStatuses::inRandomOrder()->value('id') ?? \App\Models\StudentStatuses::factory()->create()->id,
    'student_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'education_grade_id' => \App\Models\EducationGrades::inRandomOrder()->value('id') ?? \App\Models\EducationGrades::factory()->create()->id,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? \App\Models\AcademicPeriods::factory()->create()->id,
    'start_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'start_year' => $this->faker->numberBetween(1, 1000),
    'end_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'end_year' => $this->faker->numberBetween(1, 1000),
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'previous_institution_student_id' => $this->faker->word(),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
