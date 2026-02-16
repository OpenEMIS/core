<?php

namespace Database\Factories;

use App\Models\StaffStatuses;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StaffStatusesFactory extends Factory
{
    protected $model = StaffStatuses::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'code' => $this->faker->lexify(str_repeat("?", 100)),
    'name' => $this->faker->lexify(str_repeat("?", 250)),
];
    }
}
