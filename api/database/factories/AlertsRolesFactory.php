<?php

namespace Database\Factories;

use App\Models\AlertsRoles;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AlertsRolesFactory extends Factory
{
    protected $model = AlertsRoles::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $alert_rule_id = $this->faker->randomElement(\App\Models\AlertsRoles::pluck('alert_rule_id')->toArray()) ?? 1;
            $security_role_id = $this->faker->randomElement(\App\Models\AlertsRoles::pluck('security_role_id')->toArray()) ?? 1;
    $exists = AlertsRoles::where('alert_rule_id', $alert_rule_id)
                ->where('security_role_id', $security_role_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'alert_rule_id' => \App\Models\AlertRules::inRandomOrder()->value('id') ?? \App\Models\AlertRules::factory()->create()->id,
    'security_role_id' => \App\Models\SecurityRoles::inRandomOrder()->value('id') ?? \App\Models\SecurityRoles::factory()->create()->id,
];
    }
}
