<?php

namespace Database\Factories;

use App\Models\CompetencyItemsPeriods;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class CompetencyItemsPeriodsFactory extends Factory
{
    protected $model = CompetencyItemsPeriods::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $competency_item_id = $this->faker->randomElement(\App\Models\CompetencyItemsPeriods::pluck('competency_item_id')->toArray()) ?? 1;
            $competency_period_id = $this->faker->randomElement(\App\Models\CompetencyItemsPeriods::pluck('competency_period_id')->toArray()) ?? 1;
            $academic_period_id = $this->faker->randomElement(\App\Models\CompetencyItemsPeriods::pluck('academic_period_id')->toArray()) ?? 1;
            $competency_template_id = $this->faker->randomElement(\App\Models\CompetencyItemsPeriods::pluck('competency_template_id')->toArray()) ?? 1;
    $exists = CompetencyItemsPeriods::where('competency_item_id', $competency_item_id)
                ->where('competency_period_id', $competency_period_id)
                ->where('academic_period_id', $academic_period_id)
                ->where('competency_template_id', $competency_template_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'competency_item_id' => \App\Models\CompetencyItems::inRandomOrder()->value('id') ?? 1,
    'competency_period_id' => \App\Models\CompetencyPeriods::inRandomOrder()->value('id') ?? 1,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? 1,
    'competency_template_id' => \App\Models\CompetencyTemplates::inRandomOrder()->value('id') ?? 1,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
