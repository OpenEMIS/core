<?php

namespace Database\Factories\Api5;

use App\Models\Api5\InstitutionFeeTypes;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionFeeTypesFactory extends Factory
{
    protected $model = InstitutionFeeTypes::class;

    public function definition(): array
    {

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'institution_fee_id' => \App\Models\InstitutionFees::factory()->create()->id,
    'fee_type_id' => \App\Models\FeeTypes::inRandomOrder()->value('id') ?? \App\Models\FeeTypes::factory()->create()->id,
    'amount' => $this->faker->randomFloat(2, 10, 1000),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
