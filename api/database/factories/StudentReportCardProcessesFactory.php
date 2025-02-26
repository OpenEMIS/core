<?php

namespace Database\Factories;

use App\Models\StudentReportCardProcesses;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StudentReportCardProcessesFactory extends Factory
{
    protected $model = StudentReportCardProcesses::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $student_profile_template_id = $this->faker->randomElement(\App\Models\StudentReportCardProcesses::pluck('student_profile_template_id')->toArray()) ?? 1;
            $student_id = $this->faker->randomElement(\App\Models\StudentReportCardProcesses::pluck('student_id')->toArray()) ?? 1;
    $exists = StudentReportCardProcesses::where('student_profile_template_id', $student_profile_template_id)
                ->where('student_id', $student_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'student_profile_template_id' => \App\Models\StudentProfileTemplates::inRandomOrder()->value('id') ?? \App\Models\StudentProfileTemplates::factory()->create()->id,
    'student_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'status' => $this->faker->numberBetween(1, 1000),
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? \App\Models\AcademicPeriods::factory()->create()->id,
    'education_grade_id' => \App\Models\EducationGrades::inRandomOrder()->value('id') ?? \App\Models\EducationGrades::factory()->create()->id,
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
