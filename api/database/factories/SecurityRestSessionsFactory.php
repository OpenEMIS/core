<?php

namespace Database\Factories;

use App\Models\SecurityRestSessions;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SecurityRestSessionsFactory extends Factory
{
    protected $model = SecurityRestSessions::class;

    public function definition(): array
    {


        return [
    // 'id' => $this->faker->word(),
    'access_token' => $this->faker->word(),
    'refresh_token' => $this->faker->word(),
    'expiry_date' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
