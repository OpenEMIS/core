<?php

namespace Database\Factories\Api5;

use App\Models\Api5\WorkflowRules;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class WorkflowRulesFactory extends Factory
{
    protected $model = WorkflowRules::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'rule' => $this->faker->lexify(str_repeat("?", 255)),
    'feature' => $this->faker->lexify(str_repeat("?", 100)),
    'workflow_id' => \App\Models\Workflows::inRandomOrder()->value('id') ?? \App\Models\Workflows::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
