<?php

namespace Database\Factories\Api5;

use App\Models\Api5\AlertRules;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AlertRulesFactory extends Factory
{
    protected $model = AlertRules::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'feature' => $this->faker->lexify(str_repeat("?", 50)),
    'name' => $this->faker->lexify(str_repeat("?", 100)),
    'threshold' => $this->faker->lexify(str_repeat("?", 255)),
    'enabled' => $this->faker->numberBetween(1, 1000),
    'method' => $this->faker->lexify(str_repeat("?", 50)),
    'subject' => $this->faker->lexify(str_repeat("?", 255)),
    'message' => $this->faker->text(50),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
