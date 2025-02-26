<?php

namespace Database\Factories;

use App\Models\ExaminationCentresExaminationsStudents;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ExaminationCentresExaminationsStudentsFactory extends Factory
{
    protected $model = ExaminationCentresExaminationsStudents::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $examination_centre_id = $this->faker->randomElement(\App\Models\ExaminationCentresExaminationsStudents::pluck('examination_centre_id')->toArray()) ?? 1;
            $examination_id = $this->faker->randomElement(\App\Models\ExaminationCentresExaminationsStudents::pluck('examination_id')->toArray()) ?? 1;
            $student_id = $this->faker->randomElement(\App\Models\ExaminationCentresExaminationsStudents::pluck('student_id')->toArray()) ?? 1;
    $exists = ExaminationCentresExaminationsStudents::where('examination_centre_id', $examination_centre_id)
                ->where('examination_id', $examination_id)
                ->where('student_id', $student_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'registration_number' => $this->faker->lexify(str_repeat("?", 20)),
    'examination_centre_id' => \App\Models\ExaminationCentres::inRandomOrder()->value('id') ?? 1,
    'examination_id' => \App\Models\Examinations::inRandomOrder()->value('id') ?? 1,
    'student_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? 1,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? 1,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? 1,
    'modified_user_id' => $this->faker->numberBetween(1, 1000),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 1000),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}