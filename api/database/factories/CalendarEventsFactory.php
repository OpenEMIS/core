<?php

namespace Database\Factories;

use App\Models\CalendarEvents;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class CalendarEventsFactory extends Factory
{
    protected $model = CalendarEvents::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'name' => $this->faker->lexify(str_repeat("?", 50)),
    'start_time' => $this->faker->word(),
    'end_time' => $this->faker->word(),
    'institution_shift_id' => $this->faker->numberBetween(1, 1000),
    'comment' => $this->faker->text(50),
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? \App\Models\AcademicPeriods::factory()->create()->id,
    'institution_id' => $this->faker->numberBetween(1, 1000),
    'calendar_type_id' => \App\Models\CalendarTypes::inRandomOrder()->value('id') ?? \App\Models\CalendarTypes::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
