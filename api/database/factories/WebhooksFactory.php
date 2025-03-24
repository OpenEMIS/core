<?php

namespace Database\Factories;

use App\Models\Webhooks;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class WebhooksFactory extends Factory
{
    protected $model = Webhooks::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'name' => $this->faker->lexify(str_repeat("?", 45)),
    'status' => $this->faker->numberBetween(1, 1000),
    'url' => $this->faker->lexify(str_repeat("?", 200)),
    'method' => $this->faker->lexify(str_repeat("?", 10)),
    'description' => $this->faker->text(50),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
