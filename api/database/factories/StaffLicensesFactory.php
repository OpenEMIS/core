<?php

namespace Database\Factories;

use App\Models\StaffLicenses;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StaffLicensesFactory extends Factory
{
    protected $model = StaffLicenses::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'license_number' => $this->faker->lexify(str_repeat("?", 100)),
    'issue_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'expiry_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'issuer' => $this->faker->lexify(str_repeat("?", 100)),
    'comments' => $this->faker->text(50),
    'status_id' => \App\Models\WorkflowSteps::inRandomOrder()->value('id') ?? \App\Models\WorkflowSteps::factory()->create()->id,
    'assignee_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'license_type_id' => \App\Models\LicenseTypes::inRandomOrder()->value('id') ?? \App\Models\LicenseTypes::factory()->create()->id,
    'security_user_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
