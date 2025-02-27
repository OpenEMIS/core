<?php

namespace Database\Factories;

use App\Models\TrainingSessionsTrainees;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class TrainingSessionsTraineesFactory extends Factory
{
    protected $model = TrainingSessionsTrainees::class;

    public function definition(): array
    {

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'training_session_id' => \App\Models\TrainingSessions::inRandomOrder()->value('id') ?? \App\Models\TrainingSessions::factory()->create()->id,
    'trainee_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'status' => $this->faker->numberBetween(1, 1000),
];
    }
}
