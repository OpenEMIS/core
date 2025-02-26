<?php

namespace Database\Factories;

use App\Models\InstitutionStaffLeaveArchived;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionStaffLeaveArchivedFactory extends Factory
{
    protected $model = InstitutionStaffLeaveArchived::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'date_from' => \Carbon\Carbon::now()->format("Y-m-d"),
    'date_to' => \Carbon\Carbon::now()->format("Y-m-d"),
    'start_time' => $this->faker->word(),
    'end_time' => $this->faker->word(),
    'full_day' => $this->faker->numberBetween(1, 1000),
    'comments' => $this->faker->text(50),
    'staff_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? 1,
    'staff_leave_type_id' => \App\Models\StaffLeaveTypes::inRandomOrder()->value('id') ?? 1,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? 1,
    'assignee_id' => $this->faker->numberBetween(1, 1000),
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? 1,
    'status_id' => \App\Models\WorkflowSteps::inRandomOrder()->value('id') ?? 1,
    'number_of_days' => $this->faker->randomFloat(2, 10, 1000),
    'file_name' => $this->faker->lexify(str_repeat("?", 250)),
    'file_content' => $this->faker->word(),
    'modified_user_id' => $this->faker->numberBetween(1, 1000),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 1000),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
