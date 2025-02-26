<?php

namespace Database\Factories;

use App\Models\ReportCardExcludedSecurityRoles;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ReportCardExcludedSecurityRolesFactory extends Factory
{
    protected $model = ReportCardExcludedSecurityRoles::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'report_card_id' => \App\Models\ReportCards::inRandomOrder()->value('id') ?? \App\Models\ReportCards::factory()->create()->id,
    'security_role_id' => \App\Models\SecurityRoles::inRandomOrder()->value('id') ?? \App\Models\SecurityRoles::factory()->create()->id,
];
    }
}
