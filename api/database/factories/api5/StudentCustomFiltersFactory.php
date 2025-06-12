<?php

namespace Database\Factories\Api5;

use App\Models\Api5\StudentCustomFilters;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StudentCustomFiltersFactory extends Factory
{
    protected $model = StudentCustomFilters::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'name' => $this->faker->lexify(str_repeat("?", 250)),
    'custom_module_id' => $this->faker->numberBetween(1, 1000),
    'student_custom_form_id' => $this->faker->numberBetween(1, 1000),
    'education_programme_id' => $this->faker->numberBetween(1, 1000),
    'academic_period_id' => $this->faker->numberBetween(1, 1000),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
