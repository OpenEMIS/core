<?php

namespace Database\Factories\Api5;

use App\Models\Api5\InstitutionStudentRisks;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionStudentRisksFactory extends Factory
{
    protected $model = InstitutionStudentRisks::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'average_risk' => $this->faker->randomFloat(2, 10, 1000),
    'total_risk' => $this->faker->numberBetween(1, 1000),
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? \App\Models\AcademicPeriods::factory()->create()->id,
    'risk_id' => \App\Models\Risks::inRandomOrder()->value('id') ?? \App\Models\Risks::factory()->create()->id,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'student_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
