<?php

namespace Database\Factories\Api5;

use App\Models\Api5\InfrastructureWashWastes;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InfrastructureWashWastesFactory extends Factory
{
    protected $model = InfrastructureWashWastes::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? \App\Models\AcademicPeriods::factory()->create()->id,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'infrastructure_wash_waste_type_id' => \App\Models\InfrastructureWashWasteTypes::inRandomOrder()->value('id') ?? \App\Models\InfrastructureWashWasteTypes::factory()->create()->id,
    'infrastructure_wash_waste_functionality_id' => \App\Models\InfrastructureWashWasteFunctionalities::inRandomOrder()->value('id') ?? \App\Models\InfrastructureWashWasteFunctionalities::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
