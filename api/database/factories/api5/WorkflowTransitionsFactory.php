<?php

namespace Database\Factories\Api5;

use App\Models\Api5\WorkflowTransitions;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class WorkflowTransitionsFactory extends Factory
{
    protected $model = WorkflowTransitions::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'comment' => $this->faker->text(50),
    'prev_workflow_step_name' => $this->faker->lexify(str_repeat("?", 100)),
    'workflow_step_name' => $this->faker->lexify(str_repeat("?", 100)),
    'workflow_action_name' => $this->faker->lexify(str_repeat("?", 100)),
    'workflow_model_id' => \App\Models\WorkflowModels::inRandomOrder()->value('id') ?? \App\Models\WorkflowModels::factory()->create()->id,
    'model_reference' => $this->faker->word(),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
