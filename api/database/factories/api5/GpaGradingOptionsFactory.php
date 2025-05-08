<?php

namespace Database\Factories\Api5;

use App\Models\Api5\GpaGradingOptions;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class GpaGradingOptionsFactory extends Factory
{
    protected $model = GpaGradingOptions::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'code' => $this->faker->lexify(str_repeat("?", 255)),
    'name' => $this->faker->lexify(str_repeat("?", 255)),
    'description' => $this->faker->text(50),
    'min' => $this->faker->randomFloat(2, 10, 1000),
    'max' => $this->faker->randomFloat(2, 10, 1000),
    'point' => $this->faker->randomFloat(2, 10, 1000),
    'order' => $this->faker->numberBetween(1, 1000),
    'visible' => $this->faker->numberBetween(1, 1000),
    'gpa_grading_type_id' => \App\Models\GpaGradingTypes::inRandomOrder()->value('id') ?? \App\Models\GpaGradingTypes::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
