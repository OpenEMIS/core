<?php

namespace Database\Factories\Api5;

use App\Models\Api5\AcademicPeriods;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AcademicPeriodsFactory extends Factory
{
    protected $model = AcademicPeriods::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'code' => $this->faker->lexify(str_repeat("?", 60)),
    'name' => $this->faker->lexify(str_repeat("?", 50)),
    'start_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'start_year' => $this->faker->numberBetween(1, 1000),
    'end_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'end_year' => $this->faker->numberBetween(1, 1000),
    'school_days' => $this->faker->numberBetween(1, 1000),
    'current' => $this->faker->numberBetween(1, 1000),
    'editable' => $this->faker->numberBetween(1, 1000),
    'parent_id' => $this->faker->numberBetween(1, 1000),
    'lft' => $this->faker->numberBetween(1, 1000),
    'rght' => $this->faker->numberBetween(1, 1000),
    'academic_period_level_id' => \App\Models\AcademicPeriodLevels::inRandomOrder()->value('id') ?? \App\Models\AcademicPeriodLevels::factory()->create()->id,
    'order' => $this->faker->numberBetween(1, 1000),
    'visible' => $this->faker->numberBetween(1, 1000),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
