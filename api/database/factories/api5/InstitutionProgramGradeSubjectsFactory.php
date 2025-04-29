<?php

namespace Database\Factories\Api5;

use App\Models\Api5\InstitutionProgramGradeSubjects;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionProgramGradeSubjectsFactory extends Factory
{
    protected $model = InstitutionProgramGradeSubjects::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'institution_grade_id' => \App\Models\InstitutionGrades::inRandomOrder()->value('id') ?? \App\Models\InstitutionGrades::factory()->create()->id,
    'education_grade_id' => \App\Models\EducationGrades::inRandomOrder()->value('id') ?? \App\Models\EducationGrades::factory()->create()->id,
    'education_grade_subject_id' => \App\Models\EducationSubjects::inRandomOrder()->value('id') ?? \App\Models\EducationSubjects::factory()->create()->id,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
