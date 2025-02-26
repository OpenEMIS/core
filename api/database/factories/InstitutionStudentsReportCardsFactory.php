<?php

namespace Database\Factories;

use App\Models\InstitutionStudentsReportCards;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionStudentsReportCardsFactory extends Factory
{
    protected $model = InstitutionStudentsReportCards::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $report_card_id = $this->faker->randomElement(\App\Models\InstitutionStudentsReportCards::pluck('report_card_id')->toArray()) ?? 1;
            $student_id = $this->faker->randomElement(\App\Models\InstitutionStudentsReportCards::pluck('student_id')->toArray()) ?? 1;
            $institution_id = $this->faker->randomElement(\App\Models\InstitutionStudentsReportCards::pluck('institution_id')->toArray()) ?? 1;
            $academic_period_id = $this->faker->randomElement(\App\Models\InstitutionStudentsReportCards::pluck('academic_period_id')->toArray()) ?? 1;
            $education_grade_id = $this->faker->randomElement(\App\Models\InstitutionStudentsReportCards::pluck('education_grade_id')->toArray()) ?? 1;
    $exists = InstitutionStudentsReportCards::where('report_card_id', $report_card_id)
                ->where('student_id', $student_id)
                ->where('institution_id', $institution_id)
                ->where('academic_period_id', $academic_period_id)
                ->where('education_grade_id', $education_grade_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'status' => $this->faker->numberBetween(1, 1000),
    'principal_comments' => $this->faker->text(50),
    'homeroom_teacher_comments' => $this->faker->text(50),
    'file_name' => $this->faker->lexify(str_repeat("?", 250)),
    'file_content' => $this->faker->word(),
    'file_content_pdf' => $this->faker->word(),
    'started_on' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'completed_on' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'report_card_id' => \App\Models\ReportCards::inRandomOrder()->value('id') ?? 1,
    'student_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? 1,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? 1,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? 1,
    'education_grade_id' => \App\Models\EducationGrades::inRandomOrder()->value('id') ?? 1,
    'institution_class_id' => \App\Models\InstitutionClasses::inRandomOrder()->value('id') ?? 1,
    'modified_user_id' => $this->faker->numberBetween(1, 1000),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 1000),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}