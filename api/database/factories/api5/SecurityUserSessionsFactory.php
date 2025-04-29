<?php

namespace Database\Factories\Api5;

use App\Models\Api5\SecurityUserSessions;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SecurityUserSessionsFactory extends Factory
{
    protected $model = SecurityUserSessions::class;

    public function definition(): array
    {


        return [
    'id' => (string) $this->faker->uuid(),
    'username' => (string) $this->faker->uuid(),
];
    }
}
