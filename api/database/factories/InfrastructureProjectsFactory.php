<?php

namespace Database\Factories;

use App\Models\InfrastructureProjects;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InfrastructureProjectsFactory extends Factory
{
    protected $model = InfrastructureProjects::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'code' => $this->faker->lexify(str_repeat("?", 50)),
    'name' => $this->faker->lexify(str_repeat("?", 50)),
    'description' => $this->faker->text(50),
    'funding_source_description' => $this->faker->text(50),
    'contract_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'contract_amount' => $this->faker->randomFloat(2, 10, 1000),
    'status' => $this->faker->numberBetween(1, 1000),
    'date_started' => \Carbon\Carbon::now()->format("Y-m-d"),
    'date_completed' => \Carbon\Carbon::now()->format("Y-m-d"),
    'file_name' => $this->faker->lexify(str_repeat("?", 250)),
    'file_content' => $this->faker->word(),
    'comment' => $this->faker->text(50),
    'infrastructure_project_funding_source_id' => \App\Models\InfrastructureProjectFundingSources::inRandomOrder()->value('id') ?? \App\Models\InfrastructureProjectFundingSources::factory()->create()->id,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
