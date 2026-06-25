<?php

namespace Database\Factories\Api5;

use App\Models\Api5\StaffSalaryTransactions;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StaffSalaryTransactionsFactory extends Factory
{
    protected $model = StaffSalaryTransactions::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'amount' => $this->faker->randomFloat(2, 10, 1000),
    'salary_addition_type_id' => \App\Models\SalaryAdditionTypes::inRandomOrder()->value('id') ?? \App\Models\SalaryAdditionTypes::factory()->create()->id,
    'salary_deduction_type_id' => \App\Models\SalaryDeductionTypes::inRandomOrder()->value('id') ?? \App\Models\SalaryDeductionTypes::factory()->create()->id,
    'staff_salary_id' => \App\Models\StaffSalaries::inRandomOrder()->value('id') ?? \App\Models\StaffSalaries::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
