<?php

namespace Database\Factories;

use App\Models\InfrastructureUtilityInternets;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InfrastructureUtilityInternetsFactory extends Factory
{
    protected $model = InfrastructureUtilityInternets::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'comment' => $this->faker->text(50),
    'internet_purpose' => $this->faker->numberBetween(1, 1000),
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? 1,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? 1,
    'utility_internet_bandwidth_id' => \App\Models\UtilityInternetBandwidths::inRandomOrder()->value('id') ?? 1,
    'utility_internet_type_id' => \App\Models\UtilityInternetTypes::inRandomOrder()->value('id') ?? 1,
    'utility_internet_condition_id' => \App\Models\UtilityInternetConditions::inRandomOrder()->value('id') ?? 1,
    'modified_user_id' => $this->faker->numberBetween(1, 1000),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 1000),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
