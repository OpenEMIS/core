<?php

namespace Database\Factories;

use App\Models\SecurityUserSessions;
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
