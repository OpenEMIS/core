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

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'competency_item_id' =>  \App\Models\CompetencyItems::factory()->create()->id,
    'competency_period_id' =>  \App\Models\CompetencyPeriods::factory()->create()->id,
    'academic_period_id' => \App\Models\AcademicPeriods::factory()->create()->id,
    'competency_template_id' => \App\Models\CompetencyTemplates::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
