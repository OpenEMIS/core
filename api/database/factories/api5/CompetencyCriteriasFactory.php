<?php

namespace Database\Factories\Api5;

use App\Models\Api5\CompetencyCriterias;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class CompetencyCriteriasFactory extends Factory
{
    protected $model = CompetencyCriterias::class;

    public function definition(): array
    {

        return [
    'id' => $this->model::max('id') + 1,
    'code' => $this->faker->lexify(str_repeat("?", 20)),
    'name' => $this->faker->lexify(str_repeat("?", 500)),
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? \App\Models\AcademicPeriods::factory()->create()->id,
    'competency_item_id' => \App\Models\CompetencyItems::inRandomOrder()->value('id') ?? \App\Models\CompetencyItems::factory()->create()->id,
    'competency_template_id' => \App\Models\CompetencyTemplates::inRandomOrder()->value('id') ?? \App\Models\CompetencyTemplates::factory()->create()->id,
    'competency_grading_type_id' => \App\Models\CompetencyGradingTypes::inRandomOrder()->value('id') ?? \App\Models\CompetencyGradingTypes::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
