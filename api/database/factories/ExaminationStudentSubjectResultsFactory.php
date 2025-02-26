<?php

namespace Database\Factories;

use App\Models\ExaminationStudentSubjectResults;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ExaminationStudentSubjectResultsFactory extends Factory
{
    protected $model = ExaminationStudentSubjectResults::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $examination_subject_id = $this->faker->randomElement(\App\Models\ExaminationStudentSubjectResults::pluck('examination_subject_id')->toArray()) ?? 1;
            $student_id = $this->faker->randomElement(\App\Models\ExaminationStudentSubjectResults::pluck('student_id')->toArray()) ?? 1;
    $exists = ExaminationStudentSubjectResults::where('examination_subject_id', $examination_subject_id)
                ->where('student_id', $student_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'marks' => $this->faker->randomFloat(2, 10, 1000),
    'examination_subject_id' => \App\Models\ExaminationSubjects::inRandomOrder()->value('id') ?? 1,
    'student_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? 1,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? 1,
    'examination_id' => \App\Models\Examinations::inRandomOrder()->value('id') ?? 1,
    'examination_centre_id' => \App\Models\ExaminationCentres::inRandomOrder()->value('id') ?? 1,
    'education_subject_id' => \App\Models\EducationSubjects::inRandomOrder()->value('id') ?? 1,
    'examination_grading_option_id' => \App\Models\ExaminationGradingOptions::inRandomOrder()->value('id') ?? 1,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? 1,
    'modified_user_id' => $this->faker->numberBetween(1, 1000),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 1000),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}