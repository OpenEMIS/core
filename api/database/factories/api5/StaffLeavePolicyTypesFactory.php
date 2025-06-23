<?php

namespace Database\Factories\Api5;

use App\Models\Api5\StaffLeavePolicyTypes;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StaffLeavePolicyTypesFactory extends Factory
{
    protected $model = StaffLeavePolicyTypes::class;

    public function definition(): array
    {


        return [
    'id' => $this->faker->uuid(),
    'staff_leave_policy_id' => \App\Models\StaffLeavePolicies::create()->id,
    'staff_leave_type_id' => \App\Models\StaffLeaveTypes::inRandomOrder()->value('id') ?? 1,
    'days' => $this->faker->numberBetween(1, 1000),
    'rollover' => $this->faker->numberBetween(0, 9),
    ];
    }
}
