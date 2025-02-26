<?php

namespace Database\Factories;

use App\Models\EducationSubjectsFieldOfStudies;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class EducationSubjectsFieldOfStudiesFactory extends Factory
{
    protected $model = EducationSubjectsFieldOfStudies::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $education_subject_id = $this->faker->randomElement(\App\Models\EducationSubjectsFieldOfStudies::pluck('education_subject_id')->toArray()) ?? 1;
            $education_field_of_study_id = $this->faker->randomElement(\App\Models\EducationSubjectsFieldOfStudies::pluck('education_field_of_study_id')->toArray()) ?? 1;
    $exists = EducationSubjectsFieldOfStudies::where('education_subject_id', $education_subject_id)
                ->where('education_field_of_study_id', $education_field_of_study_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'education_subject_id' => \App\Models\EducationSubjects::inRandomOrder()->value('id') ?? 1,
    'education_field_of_study_id' => \App\Models\EducationFieldOfStudies::inRandomOrder()->value('id') ?? 1,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
