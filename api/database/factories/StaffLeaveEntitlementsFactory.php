<?php

namespace Database\Factories;

use App\Models\StaffLeaveEntitlements;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StaffLeaveEntitlementsFactory extends Factory
{
    protected $model = StaffLeaveEntitlements::class;

    public function definition(): array
    {


        return [
    'id' => $this->faker->numberBetween(1, 1000),
    'staff_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? 1,
    'staff_leave_type_id' => \App\Models\StaffLeaveTypes::inRandomOrder()->value('id') ?? 1,
    'adjustment' => $this->faker->numberBetween(1, 1000),
    'modified_user_id' => $this->faker->numberBetween(1, 1000),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 1000),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
