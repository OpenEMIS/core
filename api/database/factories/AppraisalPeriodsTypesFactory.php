<?php

namespace Database\Factories;

use App\Models\AppraisalPeriodsTypes;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AppraisalPeriodsTypesFactory extends Factory
{
    protected $model = AppraisalPeriodsTypes::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $appraisal_period_id = $this->faker->randomElement(\App\Models\AppraisalPeriodsTypes::pluck('appraisal_period_id')->toArray()) ?? 1;
            $appraisal_type_id = $this->faker->randomElement(\App\Models\AppraisalPeriodsTypes::pluck('appraisal_type_id')->toArray()) ?? 1;
    $exists = AppraisalPeriodsTypes::where('appraisal_period_id', $appraisal_period_id)
                ->where('appraisal_type_id', $appraisal_type_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'appraisal_period_id' => \App\Models\AppraisalPeriods::inRandomOrder()->value('id') ?? 1,
    'appraisal_type_id' => \App\Models\AppraisalTypes::inRandomOrder()->value('id') ?? 1,
];
    }
}