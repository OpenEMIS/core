<?php

namespace Database\Factories;

use App\Models\CalendarEventDates;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class CalendarEventDatesFactory extends Factory
{
    protected $model = CalendarEventDates::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'calendar_event_id' => \App\Models\CalendarEvents::inRandomOrder()->value('id') ?? \App\Models\CalendarEvents::factory()->create()->id,
    'date' => \Carbon\Carbon::now()->format("Y-m-d"),
];
    }
}
