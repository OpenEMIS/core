<?php

namespace Database\Factories\Api5;

use App\Models\Api5\InstitutionPositions;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionPositionsFactory extends Factory
{
    protected $model = InstitutionPositions::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'status_id' => \App\Models\WorkflowSteps::inRandomOrder()->value('id') ?? \App\Models\WorkflowSteps::factory()->create()->id,
    'position_no' => $this->faker->lexify(str_repeat("?", 30)),
    'staff_position_title_id' => \App\Models\StaffPositionTitles::inRandomOrder()->value('id') ?? \App\Models\StaffPositionTitles::factory()->create()->id,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'assignee_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'shift_id' => \App\Models\ShiftOptions::inRandomOrder()->value('id') ?? \App\Models\ShiftOptions::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
