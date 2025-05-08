<?php

namespace Database\Factories\Api5;

use App\Models\Api5\SystemProcesses;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SystemProcessesFactory extends Factory
{
    protected $model = SystemProcesses::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'name' => $this->faker->lexify(str_repeat("?", 50)),
    'process_id' => $this->faker->numberBetween(1, 1000),
    'callable_event' => $this->faker->lexify(str_repeat("?", 50)),
    'status' => $this->faker->numberBetween(1, 1000),
    'executed_count' => $this->faker->numberBetween(1, 1000),
    'start_date' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'end_date' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'model' => $this->faker->lexify(str_repeat("?", 100)),
    'params' => $this->faker->text(50),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
