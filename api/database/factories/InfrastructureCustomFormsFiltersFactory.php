<?php

namespace Database\Factories;

use App\Models\InfrastructureCustomFormsFilters;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InfrastructureCustomFormsFiltersFactory extends Factory
{
    protected $model = InfrastructureCustomFormsFilters::class;

    public function definition(): array
    {


        return [
//    // 'id' => $this->faker->word(),
    'infrastructure_custom_form_id' => \App\Models\InfrastructureCustomForms::inRandomOrder()->value('id') ?? \App\Models\InfrastructureCustomForms::factory()->create()->id,
    'infrastructure_custom_filter_id' => $this->faker->numberBetween(1, 1000),
];
    }
}
