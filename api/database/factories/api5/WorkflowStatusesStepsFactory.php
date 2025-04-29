<?php

namespace Database\Factories\Api5;

use App\Models\Api5\WorkflowStatusesSteps;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class WorkflowStatusesStepsFactory extends Factory
{
    protected $model = WorkflowStatusesSteps::class;

    public function definition(): array
    {


        return [
    // 'id' => $this->faker->word(),
    'workflow_status_id' => \App\Models\WorkflowStatuses::inRandomOrder()->value('id') ?? \App\Models\WorkflowStatuses::factory()->create()->id,
    'workflow_step_id' => \App\Models\WorkflowSteps::inRandomOrder()->value('id') ?? \App\Models\WorkflowSteps::factory()->create()->id,
];
    }
}
