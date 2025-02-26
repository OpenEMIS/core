<?php

namespace Database\Factories;

use App\Models\InstitutionFeeTypes;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionFeeTypesFactory extends Factory
{
    protected $model = InstitutionFeeTypes::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $institution_fee_id = $this->faker->randomElement(\App\Models\InstitutionFeeTypes::pluck('institution_fee_id')->toArray()) ?? 1;
            $fee_type_id = $this->faker->randomElement(\App\Models\InstitutionFeeTypes::pluck('fee_type_id')->toArray()) ?? 1;
    $exists = InstitutionFeeTypes::where('institution_fee_id', $institution_fee_id)
                ->where('fee_type_id', $fee_type_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'institution_fee_id' => \App\Models\InstitutionFees::inRandomOrder()->value('id') ?? 1,
    'fee_type_id' => \App\Models\FeeTypes::inRandomOrder()->value('id') ?? 1,
    'amount' => $this->faker->randomFloat(2, 10, 1000),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}