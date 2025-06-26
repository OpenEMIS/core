<?php

namespace Database\Factories\Api5;

use App\Models\Api5\UserEmployments;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class UserEmploymentsFactory extends Factory
{
    protected $model = UserEmployments::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'date_from' => \Carbon\Carbon::now()->format("Y-m-d"),
    'date_to' => \Carbon\Carbon::now()->format("Y-m-d"),
    'organisation' => $this->faker->lexify(str_repeat("?", 100)),
    'position' => $this->faker->lexify(str_repeat("?", 100)),
    'security_user_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'industry_id' => \App\Models\Industries::inRandomOrder()->value('id') ?? \App\Models\Industries::factory()->create()->id,
];
    }
}
