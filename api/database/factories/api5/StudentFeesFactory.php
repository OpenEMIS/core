<?php

namespace Database\Factories\Api5;

use App\Models\Api5\StudentFees;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StudentFeesFactory extends Factory
{
    protected $model = StudentFees::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'amount' => $this->faker->randomFloat(2, 10, 1000),
    'payment_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'comments' => $this->faker->text(50),
    'student_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'institution_fee_id' => \App\Models\InstitutionFees::inRandomOrder()->value('id') ?? \App\Models\InstitutionFees::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
