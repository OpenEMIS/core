<?php

namespace Database\Factories\Api5;

use App\Models\Api5\EducationGradesSubjects;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class EducationGradesSubjectsFactory extends Factory
{
    protected $model = EducationGradesSubjects::class;

    public function definition(): array
    {

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'hours_required' => $this->faker->randomFloat(2, 10, 1000),
    'visible' => $this->faker->numberBetween(1, 1000),
    'auto_allocation' => $this->faker->numberBetween(1, 1000),
    'education_grade_id' => \App\Models\EducationGrades::inRandomOrder()->value('id') ?? \App\Models\EducationGrades::factory()->create()->id,
    'education_subject_id' =>  \App\Models\EducationSubjects::factory()->create()->id,
    'requirement' => $this->faker->lexify(str_repeat("?", 100)),
    'result_type' => $this->faker->lexify(str_repeat("?", 255)),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
