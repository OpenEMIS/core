<?php

namespace Database\Factories;

use App\Models\InstitutionCaseRecords;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionCaseRecordsFactory extends Factory
{
    protected $model = InstitutionCaseRecords::class;

    public function definition(): array
    {

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'institution_case_id' =>  \App\Models\InstitutionCases::factory()->create()->id,
    'record_id' => $this->faker->numberBetween(1, 1000),
    'feature' => $this->faker->lexify(str_repeat("?", 100)),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
