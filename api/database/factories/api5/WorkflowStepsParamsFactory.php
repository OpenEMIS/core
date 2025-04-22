<?php

namespace Database\Factories\Api5;

use App\Models\Api5\WorkflowStepsParams;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class WorkflowStepsParamsFactory extends Factory
{
    protected $model = WorkflowStepsParams::class;

    public function definition(): array
    {


        return [
    // 'id' => $this->faker->word(),
    'workflow_step_id' => \App\Models\WorkflowSteps::inRandomOrder()->value('id') ?? \App\Models\WorkflowSteps::factory()->create()->id,
    'name' => $this->faker->lexify(str_repeat("?", 100)),
    'value' => $this->faker->lexify(str_repeat("?", 100)),
];
    }
}
