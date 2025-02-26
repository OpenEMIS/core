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
        $attempts = 0;
do {
    $staff_qualification_id = $this->faker->randomElement(\App\Models\StaffQualificationsSubjects::pluck('staff_qualification_id')->toArray()) ?? 1;
            $education_subject_id = $this->faker->randomElement(\App\Models\StaffQualificationsSubjects::pluck('education_subject_id')->toArray()) ?? 1;
    $exists = StaffQualificationsSubjects::where('staff_qualification_id', $staff_qualification_id)
                ->where('education_subject_id', $education_subject_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'staff_qualification_id' => \App\Models\StaffQualifications::inRandomOrder()->value('id') ?? \App\Models\StaffQualifications::factory()->create()->id,
    'education_subject_id' => \App\Models\EducationSubjects::inRandomOrder()->value('id') ?? \App\Models\EducationSubjects::factory()->create()->id,
];
    }
}
