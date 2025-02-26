<?php

namespace Database\Factories;

use App\Models\InstitutionSubjectStudents;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionSubjectStudentsFactory extends Factory
{
    protected $model = InstitutionSubjectStudents::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $student_id = $this->faker->randomElement(\App\Models\InstitutionSubjectStudents::pluck('student_id')->toArray()) ?? 1;
            $institution_class_id = $this->faker->randomElement(\App\Models\InstitutionSubjectStudents::pluck('institution_class_id')->toArray()) ?? 1;
            $institution_id = $this->faker->randomElement(\App\Models\InstitutionSubjectStudents::pluck('institution_id')->toArray()) ?? 1;
            $academic_period_id = $this->faker->randomElement(\App\Models\InstitutionSubjectStudents::pluck('academic_period_id')->toArray()) ?? 1;
            $education_subject_id = $this->faker->randomElement(\App\Models\InstitutionSubjectStudents::pluck('education_subject_id')->toArray()) ?? 1;
            $education_grade_id = $this->faker->randomElement(\App\Models\InstitutionSubjectStudents::pluck('education_grade_id')->toArray()) ?? 1;
    $exists = InstitutionSubjectStudents::where('student_id', $student_id)
                ->where('institution_class_id', $institution_class_id)
                ->where('institution_id', $institution_id)
                ->where('academic_period_id', $academic_period_id)
                ->where('education_subject_id', $education_subject_id)
                ->where('education_grade_id', $education_grade_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'total_mark' => $this->faker->randomFloat(2, 10, 1000),
    'outcome_result' => $this->faker->lexify(str_repeat("?", 100)),
    'student_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? 1,
    'institution_subject_id' => \App\Models\InstitutionSubjects::inRandomOrder()->value('id') ?? 1,
    'institution_class_id' => \App\Models\InstitutionClasses::inRandomOrder()->value('id') ?? 1,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? 1,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? 1,
    'education_subject_id' => \App\Models\EducationSubjects::inRandomOrder()->value('id') ?? 1,
    'education_grade_id' => \App\Models\EducationGrades::inRandomOrder()->value('id') ?? 1,
    'student_status_id' => \App\Models\StudentStatuses::inRandomOrder()->value('id') ?? 1,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
