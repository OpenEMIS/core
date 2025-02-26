<?php

namespace Database\Factories;

use App\Models\InstitutionScheduleLessonDetails;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionScheduleLessonDetailsFactory extends Factory
{
    protected $model = InstitutionScheduleLessonDetails::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'lesson_type' => $this->faker->numberBetween(1, 1000),
    'day_of_week' => $this->faker->numberBetween(1, 1000),
    'institution_schedule_timeslot_id' => \App\Models\InstitutionScheduleTimeslots::inRandomOrder()->value('id') ?? \App\Models\InstitutionScheduleTimeslots::factory()->create()->id,
    'institution_schedule_timetable_id' => \App\Models\InstitutionScheduleTimetables::inRandomOrder()->value('id') ?? \App\Models\InstitutionScheduleTimetables::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
