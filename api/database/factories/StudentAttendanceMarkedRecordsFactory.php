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
        $attempts = 0;
do {
    $institution_id = $this->faker->randomElement(\App\Models\StudentAttendanceMarkedRecords::pluck('institution_id')->toArray()) ?? 1;
            $academic_period_id = $this->faker->randomElement(\App\Models\StudentAttendanceMarkedRecords::pluck('academic_period_id')->toArray()) ?? 1;
            $institution_class_id = $this->faker->randomElement(\App\Models\StudentAttendanceMarkedRecords::pluck('institution_class_id')->toArray()) ?? 1;
            $education_grade_id = $this->faker->randomElement(\App\Models\StudentAttendanceMarkedRecords::pluck('education_grade_id')->toArray()) ?? 1;
            $date = $this->faker->randomElement(\App\Models\StudentAttendanceMarkedRecords::pluck('date')->toArray()) ?? 1;
            $period = $this->faker->randomElement(\App\Models\StudentAttendanceMarkedRecords::pluck('period')->toArray()) ?? 1;
            $subject_id = $this->faker->randomElement(\App\Models\StudentAttendanceMarkedRecords::pluck('subject_id')->toArray()) ?? 1;
    $exists = StudentAttendanceMarkedRecords::where('institution_id', $institution_id)
                ->where('academic_period_id', $academic_period_id)
                ->where('institution_class_id', $institution_class_id)
                ->where('education_grade_id', $education_grade_id)
                ->where('date', $date)
                ->where('period', $period)
                ->where('subject_id', $subject_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? \App\Models\AcademicPeriods::factory()->create()->id,
    'institution_class_id' => \App\Models\InstitutionClasses::inRandomOrder()->value('id') ?? \App\Models\InstitutionClasses::factory()->create()->id,
    'education_grade_id' => \App\Models\EducationGrades::inRandomOrder()->value('id') ?? \App\Models\EducationGrades::factory()->create()->id,
    'date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'period' => $this->faker->numberBetween(1, 1000),
    'subject_id' => $this->faker->numberBetween(1, 1000),
    'no_scheduled_class' => $this->faker->numberBetween(1, 1000),
];
    }
}
