<?php

namespace Database\Factories;

use App\Models\DepartmentStaff;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class DepartmentStaffFactory extends Factory
{
    protected $model = DepartmentStaff::class;

    public function definition(): array
    {


        return [
            'id' => $this->faker->numberBetween(1, 1000),
            'department_id' => \App\Models\InstitutionDepartments::inRandomOrder()->value('id') ?? \App\Models\InstitutionDepartments::factory()->create()->id,
            'staff_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
        ];
    }
}
