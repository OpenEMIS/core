<?php

namespace Database\Factories;

use App\Models\Nationalities;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class NationalitiesFactory extends Factory
{
    protected $model = Nationalities::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'name' => $this->faker->lexify(str_repeat("?", 50)),
    'order' => $this->faker->numberBetween(1, 1000),
    'visible' => $this->faker->numberBetween(1, 1000),
    'editable' => $this->faker->numberBetween(1, 1000),
    'identity_type_id' => \App\Models\IdentityTypes::inRandomOrder()->value('id') ?? \App\Models\IdentityTypes::factory()->create()->id,
    'default' => $this->faker->numberBetween(1, 1000),
    'international_code' => $this->faker->lexify(str_repeat("?", 50)),
    'national_code' => $this->faker->lexify(str_repeat("?", 50)),
    'external_validation' => $this->faker->numberBetween(1, 1000),
    'is_refugee' => $this->faker->numberBetween(0, 1),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
