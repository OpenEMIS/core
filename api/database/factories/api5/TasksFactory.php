<?php

namespace Database\Factories\Api5;

use App\Models\Api5\Tasks;
use Illuminate\Database\Eloquent\Factories\Factory;

//POCOR-9694
class TasksFactory extends Factory
{
    protected $model = Tasks::class;

    //POCOR-9694
    public function definition(): array
    {
        return [
            'id' => ((int) $this->model::max('id')) + 1,
            'task_type' => $this->faker->randomElement(['webhook', 'alert', 'export', 'profile', 'import']),
            'source_table' => $this->faker->randomElement(['webhook_queue', 'alert_queue', null]),
            'source_id' => $this->faker->numberBetween(1, 9999),
            'payload_json' => ['target_url' => $this->faker->url(), 'event_key' => $this->faker->word()],
            'status' => Tasks::STATUS_NEW,
            'available_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
            'started_at' => null,
            'completed_at' => null,
            'retry_count' => 0,
            'created' => \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
        ];
    }
}
