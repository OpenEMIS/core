<?php

namespace Database\Factories;

use App\Models\SecurityRoleFunctions;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SecurityRoleFunctionsFactory extends Factory
{
    protected $model = SecurityRoleFunctions::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $security_role_id = $this->faker->randomElement(\App\Models\SecurityRoleFunctions::pluck('security_role_id')->toArray()) ?? 1;
            $security_function_id = $this->faker->randomElement(\App\Models\SecurityRoleFunctions::pluck('security_function_id')->toArray()) ?? 1;
    $exists = SecurityRoleFunctions::where('security_role_id', $security_role_id)
                ->where('security_function_id', $security_function_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    '_view' => $this->faker->numberBetween(1, 1000),
    '_edit' => $this->faker->numberBetween(1, 1000),
    '_add' => $this->faker->numberBetween(1, 1000),
    '_delete' => $this->faker->numberBetween(1, 1000),
    '_execute' => $this->faker->numberBetween(1, 1000),
    'security_role_id' => \App\Models\SecurityRoles::inRandomOrder()->value('id') ?? \App\Models\SecurityRoles::factory()->create()->id,
    'security_function_id' => \App\Models\SecurityFunctions::inRandomOrder()->value('id') ?? \App\Models\SecurityFunctions::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
