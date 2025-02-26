<?php

namespace Database\Factories;

use App\Models\OutcomePeriods;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class OutcomePeriodsFactory extends Factory
{
    protected $model = OutcomePeriods::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $id = $this->faker->randomElement(\App\Models\OutcomePeriods::pluck('id')->toArray()) ?? 1;
            $academic_period_id = $this->faker->randomElement(\App\Models\OutcomePeriods::pluck('academic_period_id')->toArray()) ?? 1;
    $exists = OutcomePeriods::where('id', $id)
                ->where('academic_period_id', $academic_period_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'id' => $this->model::max('id') + 1,
    'code' => $this->faker->lexify(str_repeat("?", 50)),
    'name' => $this->faker->lexify(str_repeat("?", 250)),
    'start_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'end_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'date_enabled' => \Carbon\Carbon::now()->format("Y-m-d"),
    'date_disabled' => \Carbon\Carbon::now()->format("Y-m-d"),
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? 1,
    'outcome_template_id' => \App\Models\OutcomeTemplates::inRandomOrder()->value('id') ?? 1,
    'modified_user_id' => $this->faker->numberBetween(1, 1000),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 1000),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
