<?php

namespace Database\Factories\Api5;

use App\Models\Api5\AssessmentPeriods;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AssessmentPeriodsFactory extends Factory
{
    protected $model = AssessmentPeriods::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'code' => $this->faker->lexify(str_repeat("?", 50)),
    'name' => $this->faker->lexify(str_repeat("?", 100)),
    'start_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'end_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'date_enabled' => \Carbon\Carbon::now()->format("Y-m-d"),
    'date_disabled' => \Carbon\Carbon::now()->format("Y-m-d"),
    'weight' => $this->faker->randomFloat(2, 10, 1000),
    'academic_term' => $this->faker->lexify(str_repeat("?", 250)),
    'assessment_id' => \App\Models\Assessments::inRandomOrder()->value('id') ?? \App\Models\Assessments::factory()->create()->id,
    'editable_student_statuses' => $this->faker->numberBetween(1, 1000),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
