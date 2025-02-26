<?php

namespace Database\Factories;

use App\Models\InstitutionStaffPositionProfiles;
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
    'institution_staff_id' => \App\Models\InstitutionStaff::inRandomOrder()->value('id') ?? 1,
    'staff_change_type_id' => \App\Models\StaffChangeTypes::inRandomOrder()->value('id') ?? 1,
    'status_id' => \App\Models\WorkflowSteps::inRandomOrder()->value('id') ?? 1,
    'FTE' => $this->faker->randomFloat(2, 10, 1000),
    'start_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'end_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'staff_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? 1,
    'staff_type_id' => \App\Models\StaffTypes::inRandomOrder()->value('id') ?? 1,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? 1,
    'assignee_id' => $this->faker->numberBetween(1, 1000),
    'institution_position_id' => \App\Models\InstitutionPositions::inRandomOrder()->value('id') ?? 1,
    'modified_user_id' => $this->faker->numberBetween(1, 1000),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 1000),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
