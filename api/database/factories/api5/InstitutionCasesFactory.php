<?php

namespace Database\Factories\Api5;

use App\Models\Api5\InstitutionCases;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionCasesFactory extends Factory
{
    protected $model = InstitutionCases::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'case_number' => $this->faker->lexify(str_repeat("?", 50)),
    'title' => $this->faker->lexify(str_repeat("?", 255)),
    'description' => $this->faker->text(50),
    'status_id' => \App\Models\WorkflowSteps::inRandomOrder()->value('id') ?? \App\Models\WorkflowSteps::factory()->create()->id,
    'assignee_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'case_type_id' => \App\Models\CaseTypes::inRandomOrder()->value('id') ?? \App\Models\CaseTypes::factory()->create()->id,
    'case_priority_id' => \App\Models\CasePriorities::inRandomOrder()->value('id') ?? \App\Models\CasePriorities::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
