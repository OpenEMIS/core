<?php

namespace Database\Factories\Api5;

use App\Models\Api5\MoodleApiLog;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class MoodleApiLogFactory extends Factory
{
    protected $model = MoodleApiLog::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'action' => $this->faker->lexify(str_repeat("?", 255)),
    'params' => $this->faker->text(50),
    'response' => $this->faker->text(50),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'status' => $this->faker->numberBetween(1, 1000),
    'callback' => $this->faker->lexify(str_repeat("?", 255)),
    'callback_param' => $this->faker->text(50),
];
    }
}
