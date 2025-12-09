<?php

namespace Database\Factories\Api5;

use App\Models\Api5\ExaminationStudentSubjects;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ExaminationStudentSubjectsFactory extends Factory
{
    protected $model = ExaminationStudentSubjects::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'student_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'examination_subject_id' => \App\Models\ExaminationSubjects::inRandomOrder()->value('id') ?? \App\Models\ExaminationSubjects::factory()->create()->id,
];
    }
}
