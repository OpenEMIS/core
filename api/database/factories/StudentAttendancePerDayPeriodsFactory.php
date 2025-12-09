<?php

namespace Database\Factories;

use App\Models\StudentAttendancePerDayPeriods;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StudentAttendancePerDayPeriodsFactory extends Factory
{
    protected $model = StudentAttendancePerDayPeriods::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'name' => $this->faker->lexify(str_repeat("?", 255)),
    'student_attendance_mark_type_id' => \App\Models\StudentAttendanceMarkTypes::inRandomOrder()->value('id') ?? \App\Models\StudentAttendanceMarkTypes::factory()->create()->id,
    'period' => $this->faker->numberBetween(1, 1000),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'order' => $this->faker->numberBetween(1, 1000),
];
    }
}
