<?php

namespace Database\Factories\Api5;

use App\Models\Api5\InstitutionStudentAdmission;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionStudentAdmissionFactory extends Factory
{
    protected $model = InstitutionStudentAdmission::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'start_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'end_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'student_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'status_id' => \App\Models\WorkflowSteps::inRandomOrder()->value('id') ?? \App\Models\WorkflowSteps::factory()->create()->id,
    'assignee_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? \App\Models\AcademicPeriods::factory()->create()->id,
    'education_grade_id' => \App\Models\EducationGrades::inRandomOrder()->value('id') ?? \App\Models\EducationGrades::factory()->create()->id,
    'institution_class_id' => \App\Models\InstitutionClasses::inRandomOrder()->value('id') ?? \App\Models\InstitutionClasses::factory()->create()->id,
    'test_score' => $this->faker->numberBetween(1, 1000),
    'interview_score' => $this->faker->numberBetween(1, 1000),
    'comment' => $this->faker->text(50),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
