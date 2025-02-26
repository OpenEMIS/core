<?php

namespace Database\Factories;

use App\Models\StudentMarkTypeStatusGrades;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StudentMarkTypeStatusGradesFactory extends Factory
{
    protected $model = StudentMarkTypeStatusGrades::class;

    public function definition(): array
    {
        

        return [
    'id' => $this->faker->word(),
    'education_grade_id' => \App\Models\EducationGrades::inRandomOrder()->value('id') ?? 1,
    'student_mark_type_status_id' => \App\Models\StudentMarkTypeStatuses::inRandomOrder()->value('id') ?? 1,
];
    }
}