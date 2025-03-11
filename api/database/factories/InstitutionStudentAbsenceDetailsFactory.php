<?php

namespace Database\Factories;

use App\Models\InstitutionStudentAbsenceDetails;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionStudentAbsenceDetailsFactory extends Factory
{
    protected $model = InstitutionStudentAbsenceDetails::class;

    public function definition(): array
    {

        return [
    'student_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'academic_period_id' =>  \App\Models\AcademicPeriods::factory()->create()->id,
    'institution_class_id' => \App\Models\InstitutionClasses::inRandomOrder()->value('id') ?? \App\Models\InstitutionClasses::factory()->create()->id,
    'education_grade_id' => \App\Models\EducationGrades::inRandomOrder()->value('id') ?? \App\Models\EducationGrades::factory()->create()->id,
    'date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'period' => $this->faker->numberBetween(1, 1000),
    'comment' => $this->faker->text(50),
    'absence_type_id' => \App\Models\AbsenceTypes::inRandomOrder()->value('id') ?? \App\Models\AbsenceTypes::factory()->create()->id,
    'student_absence_reason_id' => $this->faker->numberBetween(1, 1000),
    'subject_id' => $this->faker->numberBetween(1, 1000),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
