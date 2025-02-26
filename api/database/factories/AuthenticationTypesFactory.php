<?php

namespace Database\Factories;

use App\Models\AuthenticationTypes;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AuthenticationTypesFactory extends Factory
{
    protected $model = AuthenticationTypes::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::getNextId(),
    'name' => $this->faker->lexify(str_repeat("?", 50)),
];
    }
}
