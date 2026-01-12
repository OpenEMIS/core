<?php

namespace Database\Factories\Api5;

use App\Models\Api5\InstitutionAssociationStudent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionAssociationStudentFactory extends Factory
{
    protected $model = InstitutionAssociationStudent::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'security_user_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'institution_association_id' => \App\Models\InstitutionAssociations::inRandomOrder()->value('id') ?? \App\Models\InstitutionAssociations::factory()->create()->id,
    'education_grade_id' => \App\Models\EducationGrades::inRandomOrder()->value('id') ?? \App\Models\EducationGrades::factory()->create()->id,
    'student_status_id' => \App\Models\StudentStatuses::inRandomOrder()->value('id') ?? \App\Models\StudentStatuses::factory()->create()->id,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? \App\Models\AcademicPeriods::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
