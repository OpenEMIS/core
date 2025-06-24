<?php

namespace Database\Factories\Api5;

use App\Models\Api5\StudentAttendanceTypes;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StudentAttendanceTypesFactory extends Factory
{
    protected $model = StudentAttendanceTypes::class;

    public function definition(): array
    {


        return [
            'id' => $this->model::getNextId(),
            'code' => $this->faker->lexify(str_repeat("?", 25)),
    'name' => $this->faker->lexify(str_repeat("?", 25)),
];
    }
}
