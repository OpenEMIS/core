<?php

namespace Database\Factories\Api5;

use App\Models\Api5\TaskFailures;
use App\Models\Api5\Tasks;
use Illuminate\Database\Eloquent\Factories\Factory;

//POCOR-9694
class TaskFailuresFactory extends Factory
{
    protected $model = TaskFailures::class;

    //POCOR-9694
    public function definition(): array
    {
        $taskId = (int) (Tasks::max('id') ?: Tasks::factory()->create()->id);

        return [
            'id' => ((int) $this->model::max('id')) + 1,
            'task_id' => $taskId,
            'task_job_id' => null,
            'exception_class' => 'RuntimeException',
            'exception_message' => $this->faker->sentence(8),
            'stack_trace' => $this->faker->paragraph(3),
            'retry_allowed' => true,
            'created' => \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
        ];
    }
}
