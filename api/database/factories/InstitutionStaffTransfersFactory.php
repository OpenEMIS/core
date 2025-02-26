<?php

namespace Database\Factories;

use App\Models\InstitutionStaffTransfers;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionStaffTransfersFactory extends Factory
{
    protected $model = InstitutionStaffTransfers::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'staff_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'new_institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'previous_institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'status_id' => \App\Models\WorkflowSteps::inRandomOrder()->value('id') ?? \App\Models\WorkflowSteps::factory()->create()->id,
    'assignee_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'new_institution_position_id' => \App\Models\InstitutionPositions::inRandomOrder()->value('id') ?? \App\Models\InstitutionPositions::factory()->create()->id,
    'new_staff_type_id' => \App\Models\StaffTypes::inRandomOrder()->value('id') ?? \App\Models\StaffTypes::factory()->create()->id,
    'new_FTE' => $this->faker->randomFloat(2, 10, 1000),
    'new_start_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'new_end_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'previous_institution_staff_id' => \App\Models\InstitutionStaff::inRandomOrder()->value('id') ?? \App\Models\InstitutionStaff::factory()->create()->id,
    'previous_staff_type_id' => \App\Models\StaffTypes::inRandomOrder()->value('id') ?? \App\Models\StaffTypes::factory()->create()->id,
    'previous_FTE' => $this->faker->randomFloat(2, 10, 1000),
    'previous_end_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'previous_effective_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'comment' => $this->faker->text(50),
    'transfer_type' => $this->faker->numberBetween(1, 1000),
    'all_visible' => $this->faker->numberBetween(1, 1000),
    'is_homeroom' => $this->faker->numberBetween(0, 9),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
