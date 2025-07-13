<?php

namespace Database\Factories;

use App\Models\EducationGrades;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class EducationGradesFactory extends Factory
{
    protected $model = EducationGrades::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'code' => $this->faker->lexify(str_repeat("?", 50)),
    'name' => $this->faker->lexify(str_repeat("?", 150)),
    'admission_age' => $this->faker->numberBetween(1, 1000),
    'order' => $this->faker->numberBetween(1, 1000),
    'visible' => $this->faker->numberBetween(1, 1000),
    'education_stage_id' => \App\Models\EducationStages::inRandomOrder()->value('id') ?? \App\Models\EducationStages::factory()->create()->id,
    'education_programme_id' => \App\Models\EducationProgrammes::inRandomOrder()->value('id') ?? \App\Models\EducationProgrammes::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
