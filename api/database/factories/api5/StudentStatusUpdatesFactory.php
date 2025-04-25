<?php

namespace Database\Factories\Api5;

use App\Models\Api5\StudentStatusUpdates;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StudentStatusUpdatesFactory extends Factory
{
    protected $model = StudentStatusUpdates::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'model' => $this->faker->lexify(str_repeat("?", 200)),
    'model_reference' => $this->faker->lexify(str_repeat("?", 64)),
    'effective_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'execution_status' => $this->faker->numberBetween(1, 1000),
    'security_user_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? \App\Models\AcademicPeriods::factory()->create()->id,
    'education_grade_id' => \App\Models\EducationGrades::inRandomOrder()->value('id') ?? \App\Models\EducationGrades::factory()->create()->id,
    'status_id' => \App\Models\StudentStatuses::inRandomOrder()->value('id') ?? \App\Models\StudentStatuses::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
