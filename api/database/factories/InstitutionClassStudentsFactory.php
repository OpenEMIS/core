<?php

namespace Database\Factories;

use App\Models\InstitutionClassStudents;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionClassStudentsFactory extends Factory
{
    protected $model = InstitutionClassStudents::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $student_id = $this->faker->randomElement(\App\Models\InstitutionClassStudents::pluck('student_id')->toArray()) ?? 1;
            $institution_class_id = $this->faker->randomElement(\App\Models\InstitutionClassStudents::pluck('institution_class_id')->toArray()) ?? 1;
            $education_grade_id = $this->faker->randomElement(\App\Models\InstitutionClassStudents::pluck('education_grade_id')->toArray()) ?? 1;
    $exists = InstitutionClassStudents::where('student_id', $student_id)
                ->where('institution_class_id', $institution_class_id)
                ->where('education_grade_id', $education_grade_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'student_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'institution_class_id' => \App\Models\InstitutionClasses::inRandomOrder()->value('id') ?? \App\Models\InstitutionClasses::factory()->create()->id,
    'education_grade_id' => \App\Models\EducationGrades::inRandomOrder()->value('id') ?? \App\Models\EducationGrades::factory()->create()->id,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? \App\Models\AcademicPeriods::factory()->create()->id,
    'next_institution_class_id' => $this->faker->numberBetween(1, 1000),
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'student_status_id' => \App\Models\StudentStatuses::inRandomOrder()->value('id') ?? \App\Models\StudentStatuses::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
