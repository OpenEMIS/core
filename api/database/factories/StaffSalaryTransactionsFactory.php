<?php

namespace Database\Factories;

use App\Models\StaffSalaryTransactions;
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
    'salary_addition_type_id' => \App\Models\SalaryAdditionTypes::inRandomOrder()->value('id') ?? 1,
    'salary_deduction_type_id' => \App\Models\SalaryDeductionTypes::inRandomOrder()->value('id') ?? 1,
    'staff_salary_id' => \App\Models\StaffSalaries::inRandomOrder()->value('id') ?? 1,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
