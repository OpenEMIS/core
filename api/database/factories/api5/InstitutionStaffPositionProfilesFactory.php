<?php

namespace Database\Factories\Api5;

use App\Models\Api5\InstitutionStaffPositionProfiles;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionStaffPositionProfilesFactory extends Factory
{
    protected $model = InstitutionStaffPositionProfiles::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'institution_staff_id' => \App\Models\InstitutionStaff::inRandomOrder()->value('id') ?? \App\Models\InstitutionStaff::factory()->create()->id,
    'staff_change_type_id' => \App\Models\StaffChangeTypes::inRandomOrder()->value('id') ?? \App\Models\StaffChangeTypes::factory()->create()->id,
    'status_id' => \App\Models\WorkflowSteps::inRandomOrder()->value('id') ?? \App\Models\WorkflowSteps::factory()->create()->id,
    'FTE' => $this->faker->randomFloat(2, 10, 1000),
    'start_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'end_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'staff_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'staff_type_id' => \App\Models\StaffTypes::inRandomOrder()->value('id') ?? \App\Models\StaffTypes::factory()->create()->id,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'assignee_id' => $this->faker->numberBetween(1, 1000),
    'institution_position_id' => \App\Models\InstitutionPositions::inRandomOrder()->value('id') ?? \App\Models\InstitutionPositions::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
