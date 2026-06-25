<?php

namespace Database\Factories\Api5;

use App\Models\Api5\SystemUpdates;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SystemUpdatesFactory extends Factory
{
    protected $model = SystemUpdates::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'version' => $this->faker->lexify(str_repeat("?", 50)),
    'date_released' => \Carbon\Carbon::now()->format("Y-m-d"),
    'date_approved' => \Carbon\Carbon::now()->format("Y-m-d"),
    'approved_by' => $this->faker->numberBetween(1, 1000),
    'status' => $this->faker->numberBetween(1, 1000),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
