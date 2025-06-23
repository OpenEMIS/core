<?php

namespace Database\Factories\Api5;

use App\Models\Api5\UserAwards;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class UserAwardsFactory extends Factory
{
    protected $model = UserAwards::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'issue_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'award' => $this->faker->lexify(str_repeat("?", 100)),
    'issuer' => $this->faker->lexify(str_repeat("?", 100)),
    'comment' => $this->faker->text(50),
    'security_user_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
