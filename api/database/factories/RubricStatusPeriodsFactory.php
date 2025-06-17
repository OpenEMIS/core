<?php

namespace Database\Factories;

use App\Models\RubricStatusPeriods;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class RubricStatusPeriodsFactory extends Factory
{
    protected $model = RubricStatusPeriods::class;

    public function definition(): array
    {


        return [
    // 'id' => $this->faker->word(),
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? \App\Models\AcademicPeriods::factory()->create()->id,
    'rubric_status_id' => \App\Models\RubricStatuses::inRandomOrder()->value('id') ?? \App\Models\RubricStatuses::factory()->create()->id,
];
    }
}
