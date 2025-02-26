<?php

namespace Database\Factories;

use App\Models\CustomFormsFields;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class CustomFormsFieldsFactory extends Factory
{
    protected $model = CustomFormsFields::class;

    public function definition(): array
    {
        

        return [
    'id' => $this->faker->word(),
    'custom_form_id' => \App\Models\CustomForms::inRandomOrder()->value('id') ?? 1,
    'custom_field_id' => \App\Models\CustomFields::inRandomOrder()->value('id') ?? 1,
    'name' => $this->faker->lexify(str_repeat("?", 250)),
    'is_mandatory' => $this->faker->numberBetween(1, 1000),
    'is_unique' => $this->faker->numberBetween(1, 1000),
    'order' => $this->faker->numberBetween(1, 1000),
];
    }
}