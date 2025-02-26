<?php

namespace Database\Factories;

use App\Models\StudentCustomFormsFields;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StudentCustomFormsFieldsFactory extends Factory
{
    protected $model = StudentCustomFormsFields::class;

    public function definition(): array
    {
        

        return [
    'id' => $this->faker->word(),
    'student_custom_form_id' => \App\Models\StudentCustomForms::inRandomOrder()->value('id') ?? 1,
    'student_custom_field_id' => \App\Models\StudentCustomFields::inRandomOrder()->value('id') ?? 1,
    'section' => $this->faker->lexify(str_repeat("?", 250)),
    'name' => $this->faker->lexify(str_repeat("?", 250)),
    'is_mandatory' => $this->faker->numberBetween(1, 1000),
    'is_unique' => $this->faker->numberBetween(1, 1000),
    'order' => $this->faker->numberBetween(1, 1000),
];
    }
}