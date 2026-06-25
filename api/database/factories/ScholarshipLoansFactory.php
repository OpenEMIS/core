<?php

namespace Database\Factories;

use App\Models\ScholarshipLoans;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ScholarshipLoansFactory extends Factory
{
    protected $model = ScholarshipLoans::class;

    public function definition(): array
    {


        return [
    'scholarship_id' =>  \App\Models\Scholarships::factory()->create()->id,
    'interest_rate' => $this->faker->randomFloat(2, 10, 1000),
    'interest_rate_type' => $this->faker->numberBetween(1, 1000),
    'loan_term' => $this->faker->numberBetween(1, 1000),
    'scholarship_payment_frequency_id' => \App\Models\ScholarshipPaymentFrequencies::inRandomOrder()->value('id') ?? \App\Models\ScholarshipPaymentFrequencies::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
