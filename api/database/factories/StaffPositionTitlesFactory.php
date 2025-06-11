<?php

namespace Database\Factories;

use App\Models\Api5;StaffPositionTitles;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StaffPositionTitlesFactory extends Factory
{
    protected $model = StaffPositionTitles::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::getNextId(),
    'name' => $this->faker->lexify(str_repeat("?", 50)),
    'type' => $this->faker->numberBetween(1, 1000),
    'staff_position_categories_id' => $this->faker->numberBetween(1, 1000),
    'security_role_id' => \App\Models\SecurityRoles::create()->id,
    'staff_leave_policy_id' => \App\Models\StaffLeavePolicies::create()->id,
    'file_name' => $this->faker->lexify(str_repeat("?", 250)),
    'file_content' => $this->faker->word(),
    'order' => $this->faker->numberBetween(1, 1000),
    'visible' => $this->faker->numberBetween(1, 1000),
    'editable' => $this->faker->numberBetween(1, 1000),
    'default' => $this->faker->numberBetween(1, 1000),
    'international_code' => $this->faker->lexify(str_repeat("?", 50)),
    'national_code' => $this->faker->lexify(str_repeat("?", 50)),
    'modified_user_id' => $this->faker->numberBetween(1, 1000),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 1000),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
