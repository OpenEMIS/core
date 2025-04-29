<?php

namespace Database\Factories\Api5;

use App\Models\Api5\InstitutionCurricularStaff;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionCurricularStaffFactory extends Factory
{
    protected $model = InstitutionCurricularStaff::class;

    public function definition(): array
    {


        return [
//    'id' => $this->faker->lexify(str_repeat("?", 255)),
    'staff_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'institution_curricular_id' => $this->faker->numberBetween(1, 1000),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
