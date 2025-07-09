<?php

namespace Database\Factories;

use App\Models\CalendarTypes;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class CalendarTypesFactory extends Factory
{
    protected $model = CalendarTypes::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'code' => $this->faker->lexify(str_repeat("?", 50)),
    'name' => $this->faker->lexify(str_repeat("?", 50)),
    'is_institution' => $this->faker->numberBetween(0, 1),
    'is_attendance_required' => $this->faker->numberBetween(0, 1),
];
    }
}
