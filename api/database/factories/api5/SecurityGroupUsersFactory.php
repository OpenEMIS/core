<?php

namespace Database\Factories\Api5;

use App\Models\Api5\SecurityGroupUsers;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SecurityGroupUsersFactory extends Factory
{
    protected $model = SecurityGroupUsers::class;

    public function definition(): array
    {


        return [
    // 'id' => $this->faker->word(),
    'security_group_id' => $this->faker->numberBetween(1, 1000),
    'security_user_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'security_role_id' => \App\Models\SecurityRoles::inRandomOrder()->value('id') ?? \App\Models\SecurityRoles::factory()->create()->id,
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
