<?php

namespace Database\Factories;

use App\Models\CompetencyCriterias;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class CompetencyCriteriasFactory extends Factory
{
    protected $model = CompetencyCriterias::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $id = $this->faker->randomElement(\App\Models\CompetencyCriterias::pluck('id')->toArray()) ?? 1;
            $academic_period_id = $this->faker->randomElement(\App\Models\CompetencyCriterias::pluck('academic_period_id')->toArray()) ?? 1;
            $competency_item_id = $this->faker->randomElement(\App\Models\CompetencyCriterias::pluck('competency_item_id')->toArray()) ?? 1;
            $competency_template_id = $this->faker->randomElement(\App\Models\CompetencyCriterias::pluck('competency_template_id')->toArray()) ?? 1;
    $exists = CompetencyCriterias::where('id', $id)
                ->where('academic_period_id', $academic_period_id)
                ->where('competency_item_id', $competency_item_id)
                ->where('competency_template_id', $competency_template_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

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
