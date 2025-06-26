<?php

namespace Database\Factories;

use App\Models\AlertLogs;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AlertLogsFactory extends Factory
{
    protected $model = AlertLogs::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'feature' => $this->faker->lexify(str_repeat("?", 100)),
    'method' => $this->faker->lexify(str_repeat("?", 20)),
    'destination' => $this->faker->text(50),
    'status' => $this->faker->lexify(str_repeat("?", 20)),
    'subject' => $this->faker->lexify(str_repeat("?", 255)),
    'message' => $this->faker->text(50),
    'checksum' => $this->faker->word(),
    'processed_date' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
