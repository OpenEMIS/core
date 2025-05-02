<?php

namespace Database\Factories\Api5;

use App\Models\Api5\WorkflowComments;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class WorkflowCommentsFactory extends Factory
{
    protected $model = WorkflowComments::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'comment' => $this->faker->text(50),
    'workflow_record_id' => $this->faker->numberBetween(1, 1000),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
