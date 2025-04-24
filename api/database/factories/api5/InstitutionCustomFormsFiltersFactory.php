<?php

namespace Database\Factories\Api5;

use App\Models\Api5\InstitutionCustomFormsFilters;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionCustomFormsFiltersFactory extends Factory
{
    protected $model = InstitutionCustomFormsFilters::class;

    public function definition(): array
    {


        return [
    // 'id' => $this->faker->word(),
    'institution_custom_form_id' => \App\Models\InstitutionCustomForms::factory()->create()->id,
    'institution_custom_filter_id' => $this->faker->numberBetween(1, 1000),
];
    }
}
