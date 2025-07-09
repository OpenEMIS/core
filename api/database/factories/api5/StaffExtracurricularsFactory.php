<?php

namespace Database\Factories\Api5;

use App\Models\Api5\StaffExtracurriculars;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StaffExtracurricularsFactory extends Factory
{
    protected $model = StaffExtracurriculars::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'name' => $this->faker->lexify(str_repeat("?", 100)),
    'start_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'end_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'hours' => $this->faker->numberBetween(1, 1000),
    'points' => $this->faker->numberBetween(1, 1000),
    'location' => $this->faker->lexify(str_repeat("?", 255)),
    'comment' => $this->faker->text(50),
    'staff_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'extracurricular_type_id' => \App\Models\ExtracurricularTypes::inRandomOrder()->value('id') ?? \App\Models\ExtracurricularTypes::factory()->create()->id,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? \App\Models\AcademicPeriods::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
