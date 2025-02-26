<?php

namespace Database\Factories;

use App\Models\WorkflowsFilters;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class WorkflowsFiltersFactory extends Factory
{
    protected $model = WorkflowsFilters::class;

    public function definition(): array
    {
        

        return [
    'id' => $this->faker->word(),
    'workflow_id' => \App\Models\Workflows::inRandomOrder()->value('id') ?? 1,
    'filter_id' => $this->faker->numberBetween(1, 1000),
];
    }
}