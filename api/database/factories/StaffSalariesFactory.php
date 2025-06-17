<?php

namespace Database\Factories;

use App\Models\StaffSalaries;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StaffSalariesFactory extends Factory
{
    protected $model = StaffSalaries::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'salary_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'comment' => $this->faker->text(50),
    'gross_salary' => $this->faker->randomFloat(2, 10, 1000),
    'net_salary' => $this->faker->randomFloat(2, 10, 1000),
    'staff_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
