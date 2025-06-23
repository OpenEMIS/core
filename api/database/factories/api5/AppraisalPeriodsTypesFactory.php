<?php

namespace Database\Factories\Api5;

use App\Models\Api5\AppraisalPeriodsTypes;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AppraisalPeriodsTypesFactory extends Factory
{
    protected $model = AppraisalPeriodsTypes::class;

    public function definition(): array
    {

        return [
    'appraisal_period_id' => \App\Models\AppraisalPeriods::factory()->create()->id,
    'appraisal_type_id' => \App\Models\AppraisalTypes::factory()->create()->id,
];
    }
}
