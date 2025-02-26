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
        $attempts = 0;
do {
    $day_of_week = $this->faker->randomElement(\App\Models\InstitutionScheduleLessons::pluck('day_of_week')->toArray()) ?? 1;
            $institution_schedule_timeslot_id = $this->faker->randomElement(\App\Models\InstitutionScheduleLessons::pluck('institution_schedule_timeslot_id')->toArray()) ?? 1;
            $institution_schedule_timetable_id = $this->faker->randomElement(\App\Models\InstitutionScheduleLessons::pluck('institution_schedule_timetable_id')->toArray()) ?? 1;
    $exists = InstitutionScheduleLessons::where('day_of_week', $day_of_week)
                ->where('institution_schedule_timeslot_id', $institution_schedule_timeslot_id)
                ->where('institution_schedule_timetable_id', $institution_schedule_timetable_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'day_of_week' => $this->faker->numberBetween(1, 1000),
    'institution_schedule_timeslot_id' => \App\Models\InstitutionScheduleTimeslots::inRandomOrder()->value('id') ?? 1,
    'institution_schedule_timetable_id' => \App\Models\InstitutionScheduleTimetables::inRandomOrder()->value('id') ?? 1,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
