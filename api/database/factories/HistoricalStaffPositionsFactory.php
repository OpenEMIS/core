<?php

namespace Database\Factories;

use App\Models\HistoricalStaffPositions;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class HistoricalStaffPositionsFactory extends Factory
{
    protected $model = HistoricalStaffPositions::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'start_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'end_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'comments' => $this->faker->text(50),
    'file_name' => $this->faker->lexify(str_repeat("?", 250)),
    'file_content' => $this->faker->word(),
    'institution_id' => $this->faker->numberBetween(1, 1000),
    'staff_position_title_id' => $this->faker->lexify(str_repeat("?", 11)),
    'staff_id' => $this->faker->numberBetween(1, 1000),
    'staff_type_id' => $this->faker->numberBetween(1, 1000),
    'staff_status_id' => $this->faker->numberBetween(1, 1000),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
