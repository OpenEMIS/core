<?php

namespace Database\Factories;

use App\Models\CompetencyItems;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class CompetencyItemsFactory extends Factory
{
    protected $model = CompetencyItems::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $id = $this->faker->randomElement(\App\Models\CompetencyItems::pluck('id')->toArray()) ?? 1;
            $academic_period_id = $this->faker->randomElement(\App\Models\CompetencyItems::pluck('academic_period_id')->toArray()) ?? 1;
            $competency_template_id = $this->faker->randomElement(\App\Models\CompetencyItems::pluck('competency_template_id')->toArray()) ?? 1;
    $exists = CompetencyItems::where('id', $id)
                ->where('academic_period_id', $academic_period_id)
                ->where('competency_template_id', $competency_template_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'id' => $this->model::max('id') + 1,
    'name' => $this->faker->lexify(str_repeat("?", 250)),
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? \App\Models\AcademicPeriods::factory()->create()->id,
    'competency_template_id' => \App\Models\CompetencyTemplates::inRandomOrder()->value('id') ?? \App\Models\CompetencyTemplates::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
