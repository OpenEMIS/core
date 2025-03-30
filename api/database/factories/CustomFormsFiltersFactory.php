<?php

namespace Database\Factories;

use App\Models\CustomFormsFilters;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class CustomFormsFiltersFactory extends Factory
{
    protected $model = CustomFormsFilters::class;

    public function definition(): array
    {


        return [
    // 'id' => $this->faker->word(),
    'custom_form_id' => \App\Models\CustomForms::factory()->create()->id,
    'custom_filter_id' => $this->faker->numberBetween(1, 1000),
];
    }
}
