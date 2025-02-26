<?php

namespace Database\Factories;

use App\Models\ExaminationCentresExaminationsSubjectsStudents;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ExaminationCentresExaminationsSubjectsStudentsFactory extends Factory
{
    protected $model = ExaminationCentresExaminationsSubjectsStudents::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $examination_centre_id = $this->faker->randomElement(\App\Models\ExaminationCentresExaminationsSubjectsStudents::pluck('examination_centre_id')->toArray()) ?? 1;
            $examination_subject_id = $this->faker->randomElement(\App\Models\ExaminationCentresExaminationsSubjectsStudents::pluck('examination_subject_id')->toArray()) ?? 1;
            $student_id = $this->faker->randomElement(\App\Models\ExaminationCentresExaminationsSubjectsStudents::pluck('student_id')->toArray()) ?? 1;
    $exists = ExaminationCentresExaminationsSubjectsStudents::where('examination_centre_id', $examination_centre_id)
                ->where('examination_subject_id', $examination_subject_id)
                ->where('student_id', $student_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'total_mark' => $this->faker->randomFloat(2, 10, 1000),
    'examination_centre_id' => \App\Models\ExaminationCentres::inRandomOrder()->value('id') ?? 1,
    'examination_subject_id' => \App\Models\ExaminationSubjects::inRandomOrder()->value('id') ?? 1,
    'student_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? 1,
    'examination_id' => \App\Models\Examinations::inRandomOrder()->value('id') ?? 1,
    'education_subject_id' => \App\Models\EducationSubjects::inRandomOrder()->value('id') ?? 1,
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
