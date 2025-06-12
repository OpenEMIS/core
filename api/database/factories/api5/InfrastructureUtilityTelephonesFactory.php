<?php

namespace Database\Factories\Api5;

use App\Models\Api5\InfrastructureUtilityTelephones;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InfrastructureUtilityTelephonesFactory extends Factory
{
    protected $model = InfrastructureUtilityTelephones::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'comment' => $this->faker->text(50),
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? \App\Models\AcademicPeriods::factory()->create()->id,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'utility_telephone_type_id' => \App\Models\UtilityTelephoneTypes::inRandomOrder()->value('id') ?? \App\Models\UtilityTelephoneTypes::factory()->create()->id,
    'utility_telephone_condition_id' => \App\Models\UtilityTelephoneConditions::inRandomOrder()->value('id') ?? \App\Models\UtilityTelephoneConditions::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
