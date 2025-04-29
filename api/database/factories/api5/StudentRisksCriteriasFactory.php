<?php

namespace Database\Factories\Api5;

use App\Models\Api5\StudentRisksCriterias;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StudentRisksCriteriasFactory extends Factory
{
    protected $model = StudentRisksCriterias::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'value' => $this->faker->lexify(str_repeat("?", 50)),
    'institution_student_risk_id' => \App\Models\InstitutionStudentRisks::inRandomOrder()->value('id') ?? \App\Models\InstitutionStudentRisks::factory()->create()->id,
    'risk_criteria_id' => \App\Models\RiskCriterias::inRandomOrder()->value('id') ?? \App\Models\RiskCriterias::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
