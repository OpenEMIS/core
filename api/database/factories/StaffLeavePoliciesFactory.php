<?php

namespace Database\Factories;

use App\Models\StaffLeavePolicies;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StaffLeavePoliciesFactory extends Factory
{
    protected $model = StaffLeavePolicies::class;

    public function definition(): array
    {


        return [
    'id' => $this->faker->numberBetween(1, 1000),
    'code' => $this->faker->lexify(str_repeat("?", 50)),
    'name' => $this->faker->lexify(str_repeat("?", 100)),
    'description' => $this->faker->text(50),
    'modified_user_id' => $this->faker->numberBetween(1, 1000),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 1000),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
