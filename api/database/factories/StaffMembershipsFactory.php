<?php

namespace Database\Factories;

use App\Models\StaffMemberships;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StaffMembershipsFactory extends Factory
{
    protected $model = StaffMemberships::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'issue_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'membership' => $this->faker->lexify(str_repeat("?", 100)),
    'expiry_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'comment' => $this->faker->text(50),
    'staff_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
