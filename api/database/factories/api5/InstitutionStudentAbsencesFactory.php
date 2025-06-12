<?php

namespace Database\Factories\Api5;

use App\Models\Api5\InstitutionStudentAbsences;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionStudentAbsencesFactory extends Factory
{
    protected $model = InstitutionStudentAbsences::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'student_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? \App\Models\AcademicPeriods::factory()->create()->id,
    'institution_class_id' => \App\Models\InstitutionClasses::inRandomOrder()->value('id') ?? \App\Models\InstitutionClasses::factory()->create()->id,
    'education_grade_id' => \App\Models\EducationGrades::inRandomOrder()->value('id') ?? \App\Models\EducationGrades::factory()->create()->id,
    'date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'absence_type_id' => \App\Models\AbsenceTypes::inRandomOrder()->value('id') ?? \App\Models\AbsenceTypes::factory()->create()->id,
    'institution_student_absence_day_id' => \App\Models\InstitutionStudentAbsenceDays::inRandomOrder()->value('id') ?? \App\Models\InstitutionStudentAbsenceDays::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
