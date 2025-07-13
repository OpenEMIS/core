<?php

namespace Database\Factories\Api5;

use App\Models\Api5\DataManagementLogs;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class DataManagementLogsFactory extends Factory
{
    protected $model = DataManagementLogs::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'type' => $this->faker->lexify(str_repeat("?", 200)),
    'name' => $this->faker->lexify(str_repeat("?", 200)),
    'path' => $this->faker->lexify(str_repeat("?", 200)),
    'feature' => $this->faker->lexify(str_repeat("?", 200)),
    'from_academic_period_id' => $this->faker->numberBetween(1, 1000),
    'to_academic_period_id' => $this->faker->numberBetween(1, 1000),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d"),
];
    }
}
