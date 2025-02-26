<?php

namespace Database\Factories;

use App\Models\ExaminationSubjects;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ExaminationSubjectsFactory extends Factory
{
    protected $model = ExaminationSubjects::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'name' => $this->faker->lexify(str_repeat("?", 150)),
    'code' => $this->faker->lexify(str_repeat("?", 20)),
    'weight' => $this->faker->randomFloat(2, 10, 1000),
    'examination_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'start_time' => $this->faker->word(),
    'end_time' => $this->faker->word(),
    'examination_id' => \App\Models\Examinations::inRandomOrder()->value('id') ?? 1,
    'education_subject_id' => \App\Models\EducationSubjects::inRandomOrder()->value('id') ?? 1,
    'examination_grading_type_id' => \App\Models\ExaminationGradingTypes::inRandomOrder()->value('id') ?? 1,
    'modified_user_id' => $this->faker->numberBetween(1, 1000),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 1000),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
