<?php

namespace Database\Factories\Api5;

use App\Models\Api5\StaffEmploymentStatuses;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StaffEmploymentStatusesFactory extends Factory
{
    protected $model = StaffEmploymentStatuses::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'status_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'comment' => $this->faker->text(50),
    'file_name' => $this->faker->lexify(str_repeat("?", 250)),
    'file_content' => $this->faker->word(),
    'staff_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'status_type_id' => \App\Models\EmploymentStatusTypes::inRandomOrder()->value('id') ?? \App\Models\EmploymentStatusTypes::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
