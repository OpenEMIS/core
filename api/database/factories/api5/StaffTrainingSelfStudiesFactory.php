<?php

namespace Database\Factories\Api5;

use App\Models\Api5\StaffTrainingSelfStudies;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StaffTrainingSelfStudiesFactory extends Factory
{
    protected $model = StaffTrainingSelfStudies::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'training_achievement_type_id' => $this->faker->numberBetween(1, 1000),
    'title' => $this->faker->lexify(str_repeat("?", 100)),
    'description' => $this->faker->text(50),
    'objective' => $this->faker->text(50),
    'start_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'end_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'location' => $this->faker->lexify(str_repeat("?", 100)),
    'training_provider' => $this->faker->lexify(str_repeat("?", 255)),
    'hours' => $this->faker->numberBetween(1, 1000),
    'credit_hours' => $this->faker->numberBetween(1, 1000),
    'training_status_id' => $this->faker->numberBetween(1, 1000),
    'security_user_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
