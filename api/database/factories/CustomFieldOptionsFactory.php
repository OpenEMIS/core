<?php

namespace Database\Factories;

use App\Models\CustomFieldOptions;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class CustomFieldOptionsFactory extends Factory
{
    protected $model = CustomFieldOptions::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'name' => $this->faker->lexify(str_repeat("?", 250)),
    'is_default' => $this->faker->numberBetween(0, 1),
    'visible' => $this->faker->numberBetween(1, 1000),
    'order' => $this->faker->numberBetween(1, 1000),
    'custom_field_id' => \App\Models\CustomFields::inRandomOrder()->value('id') ?? \App\Models\CustomFields::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
