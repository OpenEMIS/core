<?php

namespace Database\Factories;

use App\Models\UserSpecialNeedsPlans;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class UserSpecialNeedsPlansFactory extends Factory
{
    protected $model = UserSpecialNeedsPlans::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'plan_name' => $this->faker->lexify(str_repeat("?", 250)),
    'comment' => $this->faker->text(50),
    'file_name' => $this->faker->lexify(str_repeat("?", 250)),
    'file_content' => $this->faker->word(),
    'security_user_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'academic_period_id' => $this->faker->numberBetween(1, 1000),
    'special_needs_plan_types_id' => $this->faker->numberBetween(1, 1000),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
