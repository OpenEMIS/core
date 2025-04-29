<?php

namespace Database\Factories\Api5;

use App\Models\Api5\StudentProfileSecurityRoles;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StudentProfileSecurityRolesFactory extends Factory
{
    protected $model = StudentProfileSecurityRoles::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'security_role_id' => $this->faker->numberBetween(1, 1000),
    'student_profile_template_id' => $this->faker->numberBetween(1, 1000),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
