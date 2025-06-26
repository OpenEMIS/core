<?php

namespace Database\Factories\Api5;

use App\Models\Api5\RubricStatusRoles;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class RubricStatusRolesFactory extends Factory
{
    protected $model = RubricStatusRoles::class;

    public function definition(): array
    {


        return [
    // 'id' => $this->faker->word(),
    'rubric_status_id' => \App\Models\RubricStatuses::inRandomOrder()->value('id') ?? \App\Models\RubricStatuses::factory()->create()->id,
    'security_role_id' => \App\Models\SecurityRoles::inRandomOrder()->value('id') ?? \App\Models\SecurityRoles::factory()->create()->id,
];
    }
}
