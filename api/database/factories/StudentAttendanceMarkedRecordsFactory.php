<?php

namespace Database\Factories;

use App\Models\StudentAttendanceMarkedRecords;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StudentAttendanceMarkedRecordsFactory extends Factory
{
    protected $model = StudentAttendanceMarkedRecords::class;

    public function definition(): array
    {

        return [
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'academic_period_id' =>  \App\Models\AcademicPeriods::factory()->create()->id,
    'institution_class_id' => \App\Models\InstitutionClasses::inRandomOrder()->value('id') ?? \App\Models\InstitutionClasses::factory()->create()->id,
    'education_grade_id' => \App\Models\EducationGrades::inRandomOrder()->value('id') ?? \App\Models\EducationGrades::factory()->create()->id,
    'date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'period' => $this->faker->numberBetween(1, 1000),
    'subject_id' => $this->faker->numberBetween(1, 1000),
    'no_scheduled_class' => $this->faker->numberBetween(1, 1000),
];
    }
}
