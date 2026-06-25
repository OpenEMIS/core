<?php

namespace Database\Factories\Api5;

use App\Models\Api5\StaffChangeTypes;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StaffChangeTypesFactory extends Factory
{
    protected $model = StaffChangeTypes::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'code' => $this->faker->lexify(str_repeat("?", 50)),
    'name' => $this->faker->lexify(str_repeat("?", 50)),
];
    }
}
