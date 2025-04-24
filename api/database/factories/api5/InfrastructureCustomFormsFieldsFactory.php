<?php

namespace Database\Factories\Api5;

use App\Models\Api5\InfrastructureCustomFormsFields;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InfrastructureCustomFormsFieldsFactory extends Factory
{
    protected $model = InfrastructureCustomFormsFields::class;

    public function definition(): array
    {


        return [
//    // 'id' => $this->faker->word(),
    'infrastructure_custom_form_id' => \App\Models\InfrastructureCustomForms::inRandomOrder()->value('id') ?? \App\Models\InfrastructureCustomForms::factory()->create()->id,
    'infrastructure_custom_field_id' => \App\Models\InfrastructureCustomFields::inRandomOrder()->value('id') ?? \App\Models\InfrastructureCustomFields::factory()->create()->id,
    'section' => $this->faker->lexify(str_repeat("?", 250)),
    'name' => $this->faker->lexify(str_repeat("?", 250)),
    'is_mandatory' => $this->faker->numberBetween(0, 1),
    'is_unique' => $this->faker->numberBetween(0, 1),
    'order' => $this->faker->numberBetween(1, 10),
];
    }
}
