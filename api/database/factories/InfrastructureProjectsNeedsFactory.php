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

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'infrastructure_project_id' =>  \App\Models\InfrastructureProjects::factory()->create()->id,
    'infrastructure_need_id' => \App\Models\InfrastructureNeeds::inRandomOrder()->value('id') ?? \App\Models\InfrastructureNeeds::factory()->create()->id,
];
    }
}
