<?php

namespace Database\Factories\Api5;

use App\Models\Api5\UserLanguages;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class UserLanguagesFactory extends Factory
{
    protected $model = UserLanguages::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'evaluation_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'language_id' => \App\Models\Languages::inRandomOrder()->value('id') ?? \App\Models\Languages::factory()->create()->id,
    'listening' => $this->faker->numberBetween(1, 1000),
    'speaking' => $this->faker->numberBetween(1, 1000),
    'reading' => $this->faker->numberBetween(1, 1000),
    'writing' => $this->faker->numberBetween(1, 1000),
    'security_user_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
