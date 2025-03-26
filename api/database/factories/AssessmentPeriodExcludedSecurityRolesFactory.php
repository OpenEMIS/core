<?php

namespace Database\Factories;

use App\Models\AssessmentPeriodExcludedSecurityRoles;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AssessmentPeriodExcludedSecurityRolesFactory extends Factory
{
    protected $model = AssessmentPeriodExcludedSecurityRoles::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'assessment_period_id' => \App\Models\AssessmentPeriods::inRandomOrder()->value('id') ?? \App\Models\AssessmentPeriods::factory()->create()->id,
    'security_role_id' => \App\Models\SecurityRoles::inRandomOrder()->value('id') ?? \App\Models\SecurityRoles::factory()->create()->id,
];
    }
}
