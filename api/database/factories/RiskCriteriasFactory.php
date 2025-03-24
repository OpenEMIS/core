<?php

namespace Database\Factories;

use App\Models\RiskCriterias;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class RiskCriteriasFactory extends Factory
{
    protected $model = RiskCriterias::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'criteria' => $this->faker->lexify(str_repeat("?", 50)),
    'operator' => $this->faker->numberBetween(1, 1000),
    'threshold' => $this->faker->numberBetween(1, 1000),
    'risk_value' => $this->faker->numberBetween(1, 1000),
    'risk_id' => \App\Models\Risks::inRandomOrder()->value('id') ?? \App\Models\Risks::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
