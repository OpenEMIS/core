<?php

namespace Database\Factories;

use App\Models\TrainingSessionTraineeResults;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class TrainingSessionTraineeResultsFactory extends Factory
{
    protected $model = TrainingSessionTraineeResults::class;

    public function definition(): array
    {


        return [
    // 'id' => $this->faker->word(),
    'result' => $this->faker->lexify(str_repeat("?", 10)),
    'attendance_days' => $this->faker->lexify(str_repeat("?", 10)),
    'certificate_number' => $this->faker->lexify(str_repeat("?", 10)),
    'practical' => $this->faker->lexify(str_repeat("?", 10)),
    'training_result_type_id' => \App\Models\TrainingResultTypes::inRandomOrder()->value('id') ?? \App\Models\TrainingResultTypes::factory()->create()->id,
    'trainee_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'training_session_id' => \App\Models\TrainingSessions::inRandomOrder()->value('id') ?? \App\Models\TrainingSessions::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
