<?php

namespace Database\Factories;

use App\Models\InstitutionScheduleLessons;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionScheduleLessonsFactory extends Factory
{
    protected $model = InstitutionScheduleLessons::class;

    public function definition(): array
    {

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'day_of_week' => $this->faker->numberBetween(1, 1000),
    'institution_schedule_timeslot_id' =>  \App\Models\InstitutionScheduleTimeslots::factory()->create()->id,
    'institution_schedule_timetable_id' => \App\Models\InstitutionScheduleTimetables::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
