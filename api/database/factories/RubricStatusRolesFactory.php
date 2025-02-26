<?php

namespace Database\Factories;

use App\Models\RubricStatusRoles;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class RubricStatusRolesFactory extends Factory
{
    protected $model = RubricStatusRoles::class;

    public function definition(): array
    {
        

        return [
    'id' => $this->faker->word(),
    'rubric_status_id' => \App\Models\RubricStatuses::inRandomOrder()->value('id') ?? 1,
    'security_role_id' => \App\Models\SecurityRoles::inRandomOrder()->value('id') ?? 1,
];
    }
}