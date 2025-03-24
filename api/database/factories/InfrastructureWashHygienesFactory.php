<?php

namespace Database\Factories;

use App\Models\InfrastructureWashHygienes;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InfrastructureWashHygienesFactory extends Factory
{
    protected $model = InfrastructureWashHygienes::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? \App\Models\AcademicPeriods::factory()->create()->id,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'infrastructure_wash_hygiene_type_id' => \App\Models\InfrastructureWashHygieneTypes::inRandomOrder()->value('id') ?? \App\Models\InfrastructureWashHygieneTypes::factory()->create()->id,
    'infrastructure_wash_hygiene_soapash_availability_id' => \App\Models\InfrastructureWashHygieneSoapashAvailabilities::inRandomOrder()->value('id') ?? \App\Models\InfrastructureWashHygieneSoapashAvailabilities::factory()->create()->id,
    'infrastructure_wash_hygiene_education_id' => \App\Models\InfrastructureWashHygieneEducations::inRandomOrder()->value('id') ?? \App\Models\InfrastructureWashHygieneEducations::factory()->create()->id,
    'infrastructure_wash_hygiene_total_male' => $this->faker->numberBetween(1, 1000),
    'infrastructure_wash_hygiene_total_female' => $this->faker->numberBetween(1, 1000),
    'infrastructure_wash_hygiene_total_mixed' => $this->faker->numberBetween(1, 1000),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
