<?php

namespace Database\Factories\Api5;

use App\Models\Api5\Phinxlog;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class PhinxlogFactory extends Factory
{
    protected $model = Phinxlog::class;

    public function definition(): array
    {


        return [
    'version' => $this->model::getNextId(),
    'migration_name' => $this->faker->lexify(str_repeat("?", 100)),
    'start_time' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'end_time' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'breakpoint' => $this->faker->numberBetween(0, 9),
];
    }
}
