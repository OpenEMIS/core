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
        $attempts = 0;
do {
    $institution_case_id = $this->faker->randomElement(\App\Models\InstitutionCaseRecords::pluck('institution_case_id')->toArray()) ?? 1;
            $record_id = $this->faker->randomElement(\App\Models\InstitutionCaseRecords::pluck('record_id')->toArray()) ?? 1;
            $feature = $this->faker->randomElement(\App\Models\InstitutionCaseRecords::pluck('feature')->toArray()) ?? 1;
    $exists = InstitutionCaseRecords::where('institution_case_id', $institution_case_id)
                ->where('record_id', $record_id)
                ->where('feature', $feature)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'institution_case_id' => \App\Models\InstitutionCases::inRandomOrder()->value('id') ?? 1,
    'record_id' => $this->faker->numberBetween(1, 1000),
    'feature' => $this->faker->lexify(str_repeat("?", 100)),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
