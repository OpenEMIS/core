<?php

namespace Database\Factories;

use App\Models\ScholarshipsFieldOfStudies;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ScholarshipsFieldOfStudiesFactory extends Factory
{
    protected $model = ScholarshipsFieldOfStudies::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $scholarship_id = $this->faker->randomElement(\App\Models\ScholarshipsFieldOfStudies::pluck('scholarship_id')->toArray()) ?? 1;
            $education_field_of_study_id = $this->faker->randomElement(\App\Models\ScholarshipsFieldOfStudies::pluck('education_field_of_study_id')->toArray()) ?? 1;
    $exists = ScholarshipsFieldOfStudies::where('scholarship_id', $scholarship_id)
                ->where('education_field_of_study_id', $education_field_of_study_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'scholarship_id' => \App\Models\Scholarships::inRandomOrder()->value('id') ?? 1,
    'education_field_of_study_id' => \App\Models\EducationFieldOfStudies::inRandomOrder()->value('id') ?? 1,
];
    }
}