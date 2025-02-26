<?php

namespace Database\Factories;

use App\Models\WorkflowStepsRoles;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class WorkflowStepsRolesFactory extends Factory
{
    protected $model = WorkflowStepsRoles::class;

    public function definition(): array
    {
        

        return [
    'id' => $this->faker->word(),
    'workflow_step_id' => \App\Models\WorkflowSteps::inRandomOrder()->value('id') ?? 1,
    'security_role_id' => \App\Models\SecurityRoles::inRandomOrder()->value('id') ?? 1,
];
    }
}