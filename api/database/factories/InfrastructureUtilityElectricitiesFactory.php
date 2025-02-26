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
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? 1,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? 1,
    'utility_electricity_type_id' => \App\Models\UtilityElectricityTypes::inRandomOrder()->value('id') ?? 1,
    'utility_electricity_condition_id' => \App\Models\UtilityElectricityConditions::inRandomOrder()->value('id') ?? 1,
    'modified_user_id' => $this->faker->numberBetween(1, 1000),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 1000),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
