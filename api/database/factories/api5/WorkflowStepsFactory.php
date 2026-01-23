<?php

namespace Database\Factories\Api5;

use App\Models\Api5\WorkflowSteps;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class WorkflowStepsFactory extends Factory
{
    protected $model = WorkflowSteps::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'name' => $this->faker->lexify(str_repeat("?", 100)),
    'category' => $this->faker->numberBetween(1, 1000),
    'is_editable' => $this->faker->numberBetween(0, 1),
    'is_removable' => $this->faker->numberBetween(0, 1),
    'is_system_defined' => $this->faker->numberBetween(0, 1),
    'workflow_id' => \App\Models\Workflows::inRandomOrder()->value('id') ?? \App\Models\Workflows::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
