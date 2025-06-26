<?php

namespace Database\Factories\Api5;

use App\Models\Api5\InstitutionCustomFormsFields;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionCustomFormsFieldsFactory extends Factory
{
    protected $model = InstitutionCustomFormsFields::class;

    public function definition(): array
    {


        return [
    // 'id' => $this->faker->word(),
    'institution_custom_form_id' => \App\Models\InstitutionCustomForms::factory()->create()->id,
    'institution_custom_field_id' => \App\Models\InstitutionCustomFields::factory()->create()->id,
    'section' => $this->faker->lexify(str_repeat("?", 250)),
    'name' => $this->faker->lexify(str_repeat("?", 250)),
    'is_mandatory' => $this->faker->numberBetween(0, 1),
    'is_unique' => $this->faker->numberBetween(0, 1),
    'order' => $this->faker->numberBetween(1, 1000),
];
    }
}
