<?php

namespace Database\Factories;

use App\Models\InfrastructureProjectsNeeds;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InfrastructureProjectsNeedsFactory extends Factory
{
    protected $model = InfrastructureProjectsNeeds::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $infrastructure_project_id = $this->faker->randomElement(\App\Models\InfrastructureProjectsNeeds::pluck('infrastructure_project_id')->toArray()) ?? 1;
            $infrastructure_need_id = $this->faker->randomElement(\App\Models\InfrastructureProjectsNeeds::pluck('infrastructure_need_id')->toArray()) ?? 1;
    $exists = InfrastructureProjectsNeeds::where('infrastructure_project_id', $infrastructure_project_id)
                ->where('infrastructure_need_id', $infrastructure_need_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'infrastructure_project_id' => \App\Models\InfrastructureProjects::inRandomOrder()->value('id') ?? 1,
    'infrastructure_need_id' => \App\Models\InfrastructureNeeds::inRandomOrder()->value('id') ?? 1,
];
    }
}