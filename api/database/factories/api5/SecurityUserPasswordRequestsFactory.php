<?php

namespace Database\Factories\Api5;

use App\Models\Api5\SecurityUserPasswordRequests;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SecurityUserPasswordRequestsFactory extends Factory
{
    protected $model = SecurityUserPasswordRequests::class;

    public function definition(): array
    {


        return [
    // 'id' => $this->faker->lexify(str_repeat("?", 64)),
    'expiry_date' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'user_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
