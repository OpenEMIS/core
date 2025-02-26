<?php

namespace Database\Factories;

use App\Models\EducationProgrammesNextProgrammes;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class EducationProgrammesNextProgrammesFactory extends Factory
{
    protected $model = EducationProgrammesNextProgrammes::class;

    public function definition(): array
    {


        return [
    // 'id' => $this->faker->word(),
    'education_programme_id' => \App\Models\EducationProgrammes::inRandomOrder()->value('id') ?? \App\Models\EducationProgrammes::factory()->create()->id,
    'next_programme_id' => $this->faker->numberBetween(1, 1000),
];
    }
}
