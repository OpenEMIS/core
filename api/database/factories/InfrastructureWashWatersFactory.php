<?php

namespace Database\Factories;

use App\Models\InfrastructureWashWaters;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InfrastructureWashWatersFactory extends Factory
{
    protected $model = InfrastructureWashWaters::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? \App\Models\AcademicPeriods::factory()->create()->id,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'infrastructure_wash_water_type_id' => \App\Models\InfrastructureWashWaterTypes::inRandomOrder()->value('id') ?? \App\Models\InfrastructureWashWaterTypes::factory()->create()->id,
    'infrastructure_wash_water_functionality_id' => \App\Models\InfrastructureWashWaterFunctionalities::inRandomOrder()->value('id') ?? \App\Models\InfrastructureWashWaterFunctionalities::factory()->create()->id,
    'infrastructure_wash_water_proximity_id' => \App\Models\InfrastructureWashWaterProximities::inRandomOrder()->value('id') ?? \App\Models\InfrastructureWashWaterProximities::factory()->create()->id,
    'infrastructure_wash_water_quantity_id' => \App\Models\InfrastructureWashWaterQuantities::inRandomOrder()->value('id') ?? \App\Models\InfrastructureWashWaterQuantities::factory()->create()->id,
    'infrastructure_wash_water_quality_id' => \App\Models\InfrastructureWashWaterQualities::inRandomOrder()->value('id') ?? \App\Models\InfrastructureWashWaterQualities::factory()->create()->id,
    'infrastructure_wash_water_accessibility_id' => \App\Models\InfrastructureWashWaterAccessibilities::inRandomOrder()->value('id') ?? \App\Models\InfrastructureWashWaterAccessibilities::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
