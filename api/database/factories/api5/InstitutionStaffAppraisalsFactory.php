<?php

namespace Database\Factories\Api5;

use App\Models\Api5\InstitutionStaffAppraisals;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionStaffAppraisalsFactory extends Factory
{
    protected $model = InstitutionStaffAppraisals::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'appraisal_period_from' => \Carbon\Carbon::now()->format("Y-m-d"),
    'appraisal_period_to' => \Carbon\Carbon::now()->format("Y-m-d"),
    'date_appraised' => \Carbon\Carbon::now()->format("Y-m-d"),
    'file_name' => $this->faker->lexify(str_repeat("?", 250)),
    'file_content' => $this->faker->word(),
    'comment' => $this->faker->text(50),
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'staff_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'appraisal_type_id' => \App\Models\AppraisalTypes::inRandomOrder()->value('id') ?? \App\Models\AppraisalTypes::factory()->create()->id,
    'appraisal_period_id' => \App\Models\AppraisalPeriods::inRandomOrder()->value('id') ?? \App\Models\AppraisalPeriods::factory()->create()->id,
    'appraisal_form_id' => \App\Models\AppraisalForms::inRandomOrder()->value('id') ?? \App\Models\AppraisalForms::factory()->create()->id,
    'assignee_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'status_id' => \App\Models\WorkflowSteps::inRandomOrder()->value('id') ?? \App\Models\WorkflowSteps::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
