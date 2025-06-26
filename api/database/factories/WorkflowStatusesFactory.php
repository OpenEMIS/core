<?php

namespace Database\Factories;

use App\Models\WorkflowStatuses;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class WorkflowStatusesFactory extends Factory
{
    protected $model = WorkflowStatuses::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'code' => $this->faker->lexify(str_repeat("?", 50)),
    'name' => $this->faker->lexify(str_repeat("?", 150)),
    'is_editable' => $this->faker->numberBetween(0, 1),
    'is_removable' => $this->faker->numberBetween(0, 1),
    'workflow_model_id' => \App\Models\WorkflowModels::inRandomOrder()->value('id') ?? \App\Models\WorkflowModels::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
