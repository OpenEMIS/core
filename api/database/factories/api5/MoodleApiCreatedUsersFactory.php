<?php

namespace Database\Factories\Api5;

use App\Models\Api5\MoodleApiCreatedUsers;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class MoodleApiCreatedUsersFactory extends Factory
{
    protected $model = MoodleApiCreatedUsers::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'moodle_user_id' => $this->faker->numberBetween(1, 1000),
    'core_user_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'moodle_username' => $this->faker->lexify(str_repeat("?", 255)),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
