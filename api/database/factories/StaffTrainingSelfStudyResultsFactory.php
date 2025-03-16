<?php

namespace Database\Factories;

use App\Models\StaffTrainingSelfStudyResults;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StaffTrainingSelfStudyResultsFactory extends Factory
{
    protected $model = StaffTrainingSelfStudyResults::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'staff_training_self_study_id' => \App\Models\StaffTrainingSelfStudies::inRandomOrder()->value('id') ?? \App\Models\StaffTrainingSelfStudies::factory()->create()->id,
    'pass' => $this->faker->numberBetween(1, 1000),
    'result' => $this->faker->lexify(str_repeat("?", 10)),
    'training_status_id' => $this->faker->numberBetween(1, 1000),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
