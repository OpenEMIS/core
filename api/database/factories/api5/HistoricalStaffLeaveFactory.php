<?php

namespace Database\Factories\Api5;

use App\Models\Api5\HistoricalStaffLeave;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class HistoricalStaffLeaveFactory extends Factory
{
    protected $model = HistoricalStaffLeave::class;

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
    'staff_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'staff_leave_type_id' => \App\Models\StaffLeaveTypes::inRandomOrder()->value('id') ?? \App\Models\StaffLeaveTypes::factory()->create()->id,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'number_of_days' => $this->faker->randomFloat(2, 10, 1000),
    'file_name' => $this->faker->lexify(str_repeat("?", 250)),
    'file_content' => $this->faker->word(),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
