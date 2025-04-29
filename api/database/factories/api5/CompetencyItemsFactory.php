<?php

namespace Database\Factories\Api5;

use App\Models\Api5\CompetencyItems;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class CompetencyItemsFactory extends Factory
{
    protected $model = CompetencyItems::class;

    public function definition(): array
    {

        return [
    'id' => $this->model::max('id') + 1,
    'name' => $this->faker->lexify(str_repeat("?", 250)),
    'academic_period_id' => \App\Models\AcademicPeriods::factory()->create()->id,
    'competency_template_id' =>\App\Models\CompetencyTemplates::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
