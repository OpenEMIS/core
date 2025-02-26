<?php

namespace Database\Factories;

use App\Models\InstitutionStudentTransfers;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionStudentTransfersFactory extends Factory
{
    protected $model = InstitutionStudentTransfers::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'start_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'end_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'requested_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'student_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? 1,
    'status_id' => \App\Models\WorkflowSteps::inRandomOrder()->value('id') ?? 1,
    'assignee_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? 1,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? 1,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? 1,
    'education_grade_id' => \App\Models\EducationGrades::inRandomOrder()->value('id') ?? 1,
    'institution_class_id' => \App\Models\InstitutionClasses::inRandomOrder()->value('id') ?? 1,
    'previous_institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? 1,
    'previous_academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? 1,
    'previous_education_grade_id' => \App\Models\EducationGrades::inRandomOrder()->value('id') ?? 1,
    'student_transfer_reason_id' => \App\Models\StudentTransferReasons::inRandomOrder()->value('id') ?? 1,
    'comment' => $this->faker->text(50),
    'all_visible' => $this->faker->numberBetween(1, 1000),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
