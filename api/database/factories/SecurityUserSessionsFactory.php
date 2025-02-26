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
        $attempts = 0;
do {
    $id = $this->faker->randomElement(\App\Models\SecurityUserSessions::pluck('id')->toArray()) ?? 1;
            $username = $this->faker->randomElement(\App\Models\SecurityUserSessions::pluck('username')->toArray()) ?? 1;
    $exists = SecurityUserSessions::where('id', $id)
                ->where('username', $username)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'id' => $this->faker->lexify(str_repeat("?", 40)),
    'username' => $this->faker->lexify(str_repeat("?", 50)),
];
    }
}