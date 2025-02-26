<?php

namespace Database\Factories;

use App\Models\InfrastructureUtilityElectricities;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InfrastructureUtilityElectricitiesFactory extends Factory
{
    protected $model = InfrastructureUtilityElectricities::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'comment' => $this->faker->text(50),
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? \App\Models\AcademicPeriods::factory()->create()->id,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'utility_electricity_type_id' => \App\Models\UtilityElectricityTypes::inRandomOrder()->value('id') ?? \App\Models\UtilityElectricityTypes::factory()->create()->id,
    'utility_electricity_condition_id' => \App\Models\UtilityElectricityConditions::inRandomOrder()->value('id') ?? \App\Models\UtilityElectricityConditions::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
