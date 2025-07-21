<?php

namespace Database\Factories\Api5;

use App\Models\Api5\DepartmentStaff;
use Illuminate\Database\Eloquent\Factories\Factory;

class DepartmentStaffFactory extends Factory
{
    protected $model = DepartmentStaff::class;

    public function definition(): array
    {


        return [
            'id' => $this->faker->numberBetween(1, 1000),
            'institution_department_id' => \App\Models\Api5\InstitutionDepartments::inRandomOrder()->value('id') ?? \App\Models\Api5\InstitutionDepartments::factory()->create()->id,
            'institution_staff_id' => \App\Models\Api5\InstitutionStaff::inRandomOrder()->value('id') ?? \App\Models\Api5\InstitutionStaff::factory()->create()->id,
        ];
    }
}
