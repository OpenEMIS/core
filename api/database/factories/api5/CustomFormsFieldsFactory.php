<?php

namespace Database\Factories\Api5;

use App\Models\Api5\CustomFormsFields;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class CustomFormsFieldsFactory extends Factory
{
    protected $model = CustomFormsFields::class;

    public function definition(): array
    {


        return [
    // 'id' => $this->faker->word(),
    'custom_form_id' => \App\Models\CustomForms::factory()->create()->id,
    'custom_field_id' => \App\Models\CustomFields::factory()->create()->id,
    'name' => $this->faker->lexify(str_repeat("?", 250)),
    'is_mandatory' => $this->faker->numberBetween(0, 1),
    'is_unique' => $this->faker->numberBetween(0, 1),
    'order' => $this->faker->numberBetween(1, 1000),
];
    }
}
