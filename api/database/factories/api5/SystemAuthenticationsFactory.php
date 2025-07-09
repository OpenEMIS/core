<?php

namespace Database\Factories\Api5;

use App\Models\Api5\SystemAuthentications;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SystemAuthenticationsFactory extends Factory
{
    protected $model = SystemAuthentications::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'code' => $this->faker->word(),
    'name' => $this->faker->lexify(str_repeat("?", 100)),
    'status' => $this->faker->numberBetween(1, 1000),
    'allow_create_user' => $this->faker->numberBetween(1, 1000),
    'mapped_username' => $this->faker->lexify(str_repeat("?", 100)),
    'mapped_first_name' => $this->faker->lexify(str_repeat("?", 100)),
    'mapped_last_name' => $this->faker->lexify(str_repeat("?", 100)),
    'mapped_date_of_birth' => $this->faker->lexify(str_repeat("?", 50)),
    'mapped_gender' => $this->faker->lexify(str_repeat("?", 50)),
    'mapped_role' => $this->faker->lexify(str_repeat("?", 50)),
    'mapped_email' => $this->faker->lexify(str_repeat("?", 50)),
    'authentication_type_id' => \App\Models\AuthenticationTypes::inRandomOrder()->value('id') ?? \App\Models\AuthenticationTypes::factory()->create()->id,
];
    }
}
