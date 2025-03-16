<?php

namespace Database\Factories;

use App\Models\StaffQualificationsSubjects;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StaffQualificationsSubjectsFactory extends Factory
{
    protected $model = StaffQualificationsSubjects::class;

    public function definition(): array
    {

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'staff_qualification_id' => \App\Models\StaffQualifications::inRandomOrder()->value('id') ?? \App\Models\StaffQualifications::factory()->create()->id,
    'education_subject_id' => \App\Models\EducationSubjects::inRandomOrder()->value('id') ?? \App\Models\EducationSubjects::factory()->create()->id,
];
    }
}
