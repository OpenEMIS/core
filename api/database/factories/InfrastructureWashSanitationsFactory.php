<?php

namespace Database\Factories;

use App\Models\InfrastructureWashSanitations;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InfrastructureWashSanitationsFactory extends Factory
{
    protected $model = InfrastructureWashSanitations::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? \App\Models\AcademicPeriods::factory()->create()->id,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'infrastructure_wash_sanitation_type_id' => \App\Models\InfrastructureWashSanitationTypes::inRandomOrder()->value('id') ?? \App\Models\InfrastructureWashSanitationTypes::factory()->create()->id,
    'infrastructure_wash_sanitation_use_id' => \App\Models\InfrastructureWashSanitationUses::inRandomOrder()->value('id') ?? \App\Models\InfrastructureWashSanitationUses::factory()->create()->id,
    'infrastructure_wash_sanitation_total_male' => $this->faker->numberBetween(1, 1000),
    'infrastructure_wash_sanitation_total_female' => $this->faker->numberBetween(1, 1000),
    'infrastructure_wash_sanitation_total_mixed' => $this->faker->numberBetween(1, 1000),
    'infrastructure_wash_sanitation_quality_id' => \App\Models\InfrastructureWashSanitationQualities::inRandomOrder()->value('id') ?? \App\Models\InfrastructureWashSanitationQualities::factory()->create()->id,
    'infrastructure_wash_sanitation_accessibility_id' => \App\Models\InfrastructureWashSanitationAccessibilities::inRandomOrder()->value('id') ?? \App\Models\InfrastructureWashSanitationAccessibilities::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
