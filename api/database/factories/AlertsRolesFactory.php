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

        return [
            'id' => (string)\Illuminate\Support\Str::uuid(),
            'alert_rule_id' => \App\Models\AlertRules::factory()->create()->id,
            'security_role_id' => \App\Models\SecurityRoles::factory()->create()->id,
        ];
    }
}
