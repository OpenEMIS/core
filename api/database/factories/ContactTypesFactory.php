<?php

namespace Database\Factories;

use App\Models\ContactTypes;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ContactTypesFactory extends Factory
{
    protected $model = ContactTypes::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'contact_option_id' => \App\Models\ContactOptions::inRandomOrder()->value('id') ?? \App\Models\ContactOptions::factory()->create()->id,
    'name' => $this->faker->lexify(str_repeat("?", 100)),
    'validation_pattern' => $this->faker->lexify(str_repeat("?", 100)),
    'order' => $this->faker->numberBetween(1, 1000),
    'visible' => $this->faker->numberBetween(1, 1000),
    'international_code' => $this->faker->lexify(str_repeat("?", 10)),
    'national_code' => $this->faker->lexify(str_repeat("?", 10)),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
