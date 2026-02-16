<?php

namespace Database\Factories\Api5;

use App\Models\Api5\Alerts;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AlertsFactory extends Factory
{
    protected $model = Alerts::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'name' => $this->faker->lexify(str_repeat("?", 50)),
    'process_name' => $this->faker->lexify(str_repeat("?", 50)),
    'process_id' => $this->faker->numberBetween(1, 1000),
    'frequency' => $this->faker->lexify(str_repeat("?", 255)),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
