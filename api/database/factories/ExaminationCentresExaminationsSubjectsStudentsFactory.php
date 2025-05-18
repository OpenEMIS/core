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

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'total_mark' => $this->faker->randomFloat(2, 10, 1000),
    'examination_centre_id' =>  \App\Models\ExaminationCentres::factory()->create()->id,
    'examination_subject_id' => \App\Models\ExaminationSubjects::inRandomOrder()->value('id') ?? \App\Models\ExaminationSubjects::factory()->create()->id,
    'student_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'examination_id' => \App\Models\Examinations::inRandomOrder()->value('id') ?? \App\Models\Examinations::factory()->create()->id,
    'education_subject_id' => \App\Models\EducationSubjects::inRandomOrder()->value('id') ?? \App\Models\EducationSubjects::factory()->create()->id,
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
