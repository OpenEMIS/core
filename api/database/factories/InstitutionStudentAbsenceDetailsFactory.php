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
        $attempts = 0;
do {
    $student_id = $this->faker->randomElement(\App\Models\InstitutionStudentAbsenceDetails::pluck('student_id')->toArray()) ?? 1;
            $institution_id = $this->faker->randomElement(\App\Models\InstitutionStudentAbsenceDetails::pluck('institution_id')->toArray()) ?? 1;
            $academic_period_id = $this->faker->randomElement(\App\Models\InstitutionStudentAbsenceDetails::pluck('academic_period_id')->toArray()) ?? 1;
            $institution_class_id = $this->faker->randomElement(\App\Models\InstitutionStudentAbsenceDetails::pluck('institution_class_id')->toArray()) ?? 1;
            $date = $this->faker->randomElement(\App\Models\InstitutionStudentAbsenceDetails::pluck('date')->toArray()) ?? 1;
            $period = $this->faker->randomElement(\App\Models\InstitutionStudentAbsenceDetails::pluck('period')->toArray()) ?? 1;
            $subject_id = $this->faker->randomElement(\App\Models\InstitutionStudentAbsenceDetails::pluck('subject_id')->toArray()) ?? 1;
    $exists = InstitutionStudentAbsenceDetails::where('student_id', $student_id)
                ->where('institution_id', $institution_id)
                ->where('academic_period_id', $academic_period_id)
                ->where('institution_class_id', $institution_class_id)
                ->where('date', $date)
                ->where('period', $period)
                ->where('subject_id', $subject_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'student_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? 1,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? 1,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? 1,
    'institution_class_id' => \App\Models\InstitutionClasses::inRandomOrder()->value('id') ?? 1,
    'education_grade_id' => \App\Models\EducationGrades::inRandomOrder()->value('id') ?? 1,
    'date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'period' => $this->faker->numberBetween(1, 1000),
    'comment' => $this->faker->text(50),
    'absence_type_id' => \App\Models\AbsenceTypes::inRandomOrder()->value('id') ?? 1,
    'student_absence_reason_id' => $this->faker->numberBetween(1, 1000),
    'subject_id' => $this->faker->numberBetween(1, 1000),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
