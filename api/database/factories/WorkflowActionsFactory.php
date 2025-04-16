<?php

namespace Database\Factories;

use App\Models\WorkflowActions;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class WorkflowActionsFactory extends Factory
{
    protected $model = WorkflowActions::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'name' => $this->faker->lexify(str_repeat("?", 100)),
    'description' => $this->faker->text(50),
    'action' => $this->faker->numberBetween(1, 1000),
    'visible' => $this->faker->numberBetween(1, 1000),
    'comment_required' => $this->faker->numberBetween(1, 1000),
    'allow_by_assignee' => $this->faker->numberBetween(1, 1000),
    'event_key' => $this->faker->text(50),
    'workflow_step_id' => \App\Models\WorkflowSteps::inRandomOrder()->value('id') ?? \App\Models\WorkflowSteps::factory()->create()->id,
    'next_workflow_step_id' => $this->faker->numberBetween(1, 1000),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
