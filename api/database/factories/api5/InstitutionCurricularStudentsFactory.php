<?php

namespace Database\Factories\Api5;

use App\Models\Api5\InstitutionCurricularStudents;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionCurricularStudentsFactory extends Factory
{
    protected $model = InstitutionCurricularStudents::class;

    public function definition(): array
    {


        return [
//    'id' => $this->faker->lexify(str_repeat("?", 255)),
    'student_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'institution_curricular_id' => $this->faker->numberBetween(1, 1000),
    'start_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'end_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'hours' => $this->faker->numberBetween(1, 1000),
    'points' => $this->faker->randomFloat(2, 10, 1000),
    'location' => $this->faker->lexify(str_repeat("?", 255)),
    'curricular_position_id' => $this->faker->numberBetween(1, 1000),
    'comments' => $this->faker->lexify(str_repeat("?", 255)),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
