<?php

namespace Database\Factories\Api5;

use App\Models\Api5\RubricStatusProgrammes;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class RubricStatusProgrammesFactory extends Factory
{
    protected $model = RubricStatusProgrammes::class;

    public function definition(): array
    {


        return [
    // 'id' => $this->faker->word(),
    'education_programme_id' => \App\Models\EducationProgrammes::inRandomOrder()->value('id') ?? \App\Models\EducationProgrammes::factory()->create()->id,
    'rubric_status_id' => \App\Models\RubricStatuses::inRandomOrder()->value('id') ?? \App\Models\RubricStatuses::factory()->create()->id,
];
    }
}
