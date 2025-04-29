<?php

namespace Database\Factories\Api5;

use App\Models\Api5\TrainingSessionResults;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class TrainingSessionResultsFactory extends Factory
{
    protected $model = TrainingSessionResults::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'training_session_id' => \App\Models\TrainingSessions::inRandomOrder()->value('id') ?? \App\Models\TrainingSessions::factory()->create()->id,
    'assignee_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'status_id' => \App\Models\WorkflowSteps::inRandomOrder()->value('id') ?? \App\Models\WorkflowSteps::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
