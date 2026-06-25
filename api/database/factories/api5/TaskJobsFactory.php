<?php

namespace Database\Factories\Api5;

use App\Models\Api5\TaskJobs;
use App\Models\Api5\Tasks;
use Illuminate\Database\Eloquent\Factories\Factory;

//POCOR-9694
class TaskJobsFactory extends Factory
{
    protected $model = TaskJobs::class;

    //POCOR-9694
    public function definition(): array
    {
        $taskId = (int) (Tasks::max('id') ?: Tasks::factory()->create()->id);

        return [
            'id' => ((int) $this->model::max('id')) + 1,
            'task_id' => $taskId,
            'attempt_number' => 1,
            'started_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
            'ended_at' => null,
            'duration_ms' => null,
            'status' => TaskJobs::STATUS_PROCESSING,
            'message_preview' => $this->faker->sentence(6),
            'created' => \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
        ];
    }
}
