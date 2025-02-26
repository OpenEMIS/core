<?php

namespace Database\Factories;

use App\Models\InstitutionVisitRequests;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionVisitRequestsFactory extends Factory
{
    protected $model = InstitutionVisitRequests::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'date_of_visit' => \Carbon\Carbon::now()->format("Y-m-d"),
    'comment' => $this->faker->text(50),
    'file_name' => $this->faker->lexify(str_repeat("?", 250)),
    'file_content' => $this->faker->word(),
    'status_id' => \App\Models\WorkflowSteps::inRandomOrder()->value('id') ?? \App\Models\WorkflowSteps::factory()->create()->id,
    'assignee_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? \App\Models\AcademicPeriods::factory()->create()->id,
    'quality_visit_type_id' => \App\Models\QualityVisitTypes::inRandomOrder()->value('id') ?? \App\Models\QualityVisitTypes::factory()->create()->id,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
