<?php

namespace Database\Factories\Api5;

use App\Models\Api5\StudentVisitTypes;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StudentVisitTypesFactory extends Factory
{
    protected $model = StudentVisitTypes::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'code' => $this->faker->lexify(str_repeat("?", 100)),
    'name' => $this->faker->lexify(str_repeat("?", 250)),
];
    }
}
