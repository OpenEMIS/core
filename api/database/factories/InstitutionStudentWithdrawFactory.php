<?php

namespace Database\Factories;

use App\Models\InstitutionStudentWithdraw;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionStudentWithdrawFactory extends Factory
{
    protected $model = InstitutionStudentWithdraw::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'effective_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'student_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? 1,
    'status_id' => \App\Models\WorkflowSteps::inRandomOrder()->value('id') ?? 1,
    'assignee_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? 1,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? 1,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? 1,
    'education_grade_id' => \App\Models\EducationGrades::inRandomOrder()->value('id') ?? 1,
    'student_withdraw_reason_id' => \App\Models\StudentWithdrawReasons::inRandomOrder()->value('id') ?? 1,
    'comment' => $this->faker->text(50),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
