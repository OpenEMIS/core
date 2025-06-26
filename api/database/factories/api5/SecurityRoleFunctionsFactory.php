<?php

namespace Database\Factories\Api5;

use App\Models\Api5\SecurityRoleFunctions;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SecurityRoleFunctionsFactory extends Factory
{
    protected $model = SecurityRoleFunctions::class;

    public function definition(): array
    {

        return [
    '_view' => $this->faker->boolean(),
    '_edit' => $this->faker->boolean(),
    '_add' => $this->faker->boolean(),
    '_delete' => $this->faker->boolean(),
    '_execute' => $this->faker->boolean(),
    'security_role_id' =>  \App\Models\SecurityRoles::factory()->create()->id,
    'security_function_id' => \App\Models\SecurityFunctions::inRandomOrder()->value('id') ?? \App\Models\SecurityFunctions::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
