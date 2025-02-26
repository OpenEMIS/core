<?php

namespace Database\Factories;

use App\Models\InstitutionStaff;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionStaffFactory extends Factory
{
    protected $model = InstitutionStaff::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'FTE' => $this->faker->randomFloat(2, 10, 1000),
    'start_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'start_year' => $this->faker->numberBetween(1, 1000),
    'end_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'end_year' => $this->faker->numberBetween(1, 1000),
    'staff_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? 1,
    'staff_type_id' => \App\Models\StaffTypes::inRandomOrder()->value('id') ?? 1,
    'staff_status_id' => \App\Models\StaffStatuses::inRandomOrder()->value('id') ?? 1,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? 1,
    'is_homeroom' => $this->faker->numberBetween(0, 9),
    'institution_position_id' => \App\Models\InstitutionPositions::inRandomOrder()->value('id') ?? 1,
    'security_group_user_id' => $this->faker->word(),
    'staff_position_grade_id' => \App\Models\StaffPositionGrades::inRandomOrder()->value('id') ?? 1,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
