<?php

namespace Database\Factories;

use App\Models\StaffCustomFormsFields;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StaffCustomFormsFieldsFactory extends Factory
{
    protected $model = StaffCustomFormsFields::class;

    public function definition(): array
    {
        

        return [
    'id' => $this->faker->word(),
    'staff_custom_form_id' => \App\Models\StaffCustomForms::inRandomOrder()->value('id') ?? 1,
    'staff_custom_field_id' => \App\Models\StaffCustomFields::inRandomOrder()->value('id') ?? 1,
    'section' => $this->faker->lexify(str_repeat("?", 250)),
    'name' => $this->faker->lexify(str_repeat("?", 250)),
    'is_mandatory' => $this->faker->numberBetween(1, 1000),
    'is_unique' => $this->faker->numberBetween(1, 1000),
    'order' => $this->faker->numberBetween(1, 1000),
];
    }
}