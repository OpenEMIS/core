<?php

namespace Database\Factories\Api5;

use App\Models\Api5\EducationSubjectsFieldOfStudies;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class EducationSubjectsFieldOfStudiesFactory extends Factory
{
    protected $model = EducationSubjectsFieldOfStudies::class;

    public function definition(): array
    {

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'education_subject_id' =>  \App\Models\EducationSubjects::factory()->create()->id,
    'education_field_of_study_id' => \App\Models\EducationFieldOfStudies::inRandomOrder()->value('id') ?? \App\Models\EducationFieldOfStudies::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
