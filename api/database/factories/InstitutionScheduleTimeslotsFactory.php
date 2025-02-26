<?php

namespace Database\Factories;

use App\Models\InstitutionScheduleTimeslots;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionScheduleTimeslotsFactory extends Factory
{
    protected $model = InstitutionScheduleTimeslots::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'interval' => $this->faker->numberBetween(1, 1000),
    'order' => $this->faker->numberBetween(1, 1000),
    'institution_schedule_interval_id' => \App\Models\InstitutionScheduleIntervals::inRandomOrder()->value('id') ?? \App\Models\InstitutionScheduleIntervals::factory()->create()->id,
];
    }
}
