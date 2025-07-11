<?php

namespace Database\Factories\Api5;

use App\Models\Api5\InstitutionDepartments;
use Illuminate\Database\Eloquent\Factories\Factory;

class InstitutionDepartmentsFactory extends Factory
{
    protected $model = InstitutionDepartments::class;

    public function definition(): array
    {
        return [
            'id' => $this->faker->numberBetween(1, 1000),
            'name' => $this->faker->lexify(str_repeat("?", 100)),
            'code' => $this->faker->lexify(str_repeat("?", 50)),
            'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? 1,
            'manager_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? 1,
            'modified_user_id' => $this->faker->numberBetween(1, 1000),
            'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
            'created_user_id' => $this->faker->numberBetween(1, 1000),
            'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
        ];
    }
}
