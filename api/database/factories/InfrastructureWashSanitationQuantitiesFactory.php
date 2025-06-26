<?php

namespace Database\Factories;

use App\Models\InfrastructureWashSanitationQuantities;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InfrastructureWashSanitationQuantitiesFactory extends Factory
{
    protected $model = InfrastructureWashSanitationQuantities::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'gender_id' => $this->faker->lexify(str_repeat("?", 11)),
    'functional' => $this->faker->numberBetween(1, 1000),
    'value' => $this->faker->numberBetween(1, 1000),
    'infrastructure_wash_sanitation_id' => \App\Models\InfrastructureWashSanitations::inRandomOrder()->value('id') ?? \App\Models\InfrastructureWashSanitations::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
