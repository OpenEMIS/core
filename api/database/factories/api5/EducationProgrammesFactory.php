<?php

namespace Database\Factories\Api5;

use App\Models\Api5\EducationProgrammes;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class EducationProgrammesFactory extends Factory
{
    protected $model = EducationProgrammes::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'code' => $this->faker->lexify(str_repeat("?", 50)),
    'name' => $this->faker->lexify(str_repeat("?", 150)),
    'duration' => $this->faker->numberBetween(1, 1000),
    'order' => $this->faker->numberBetween(1, 1000),
    'visible' => $this->faker->numberBetween(1, 1000),
    'education_field_of_study_id' => \App\Models\EducationFieldOfStudies::inRandomOrder()->value('id') ?? \App\Models\EducationFieldOfStudies::factory()->create()->id,
    'education_cycle_id' => \App\Models\EducationCycles::inRandomOrder()->value('id') ?? \App\Models\EducationCycles::factory()->create()->id,
    'education_certification_id' => \App\Models\EducationCertifications::inRandomOrder()->value('id') ?? \App\Models\EducationCertifications::factory()->create()->id,
    'same_grade_promotion' => $this->faker->numberBetween(1, 1000),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
