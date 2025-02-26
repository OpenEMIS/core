<?php

namespace Database\Factories;

use App\Models\StudentReportCards;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StudentReportCardsFactory extends Factory
{
    protected $model = StudentReportCards::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $student_profile_template_id = $this->faker->randomElement(\App\Models\StudentReportCards::pluck('student_profile_template_id')->toArray()) ?? 1;
            $student_id = $this->faker->randomElement(\App\Models\StudentReportCards::pluck('student_id')->toArray()) ?? 1;
            $institution_id = $this->faker->randomElement(\App\Models\StudentReportCards::pluck('institution_id')->toArray()) ?? 1;
            $academic_period_id = $this->faker->randomElement(\App\Models\StudentReportCards::pluck('academic_period_id')->toArray()) ?? 1;
    $exists = StudentReportCards::where('student_profile_template_id', $student_profile_template_id)
                ->where('student_id', $student_id)
                ->where('institution_id', $institution_id)
                ->where('academic_period_id', $academic_period_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'status' => $this->faker->numberBetween(1, 1000),
    'file_name' => $this->faker->lexify(str_repeat("?", 250)),
    'file_content' => $this->faker->word(),
    'file_content_pdf' => $this->faker->word(),
    'started_on' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'completed_on' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'student_profile_template_id' => \App\Models\StudentProfileTemplates::inRandomOrder()->value('id') ?? 1,
    'student_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? 1,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? 1,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? 1,
    'education_grade_id' => \App\Models\EducationGrades::inRandomOrder()->value('id') ?? 1,
    'modified_user_id' => $this->faker->numberBetween(1, 1000),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 1000),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}