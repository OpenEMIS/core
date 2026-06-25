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

        return [
    'scholarship_id' => \App\Models\Scholarships::inRandomOrder()->value('id') ?? \App\Models\Scholarships::factory()->create()->id,
    'education_field_of_study_id' => \App\Models\EducationFieldOfStudies::inRandomOrder()->value('id') ?? \App\Models\EducationFieldOfStudies::factory()->create()->id,
];
    }
}
