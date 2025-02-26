<?php

namespace Database\Factories;

use App\Models\ReportCardProcesses;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ReportCardProcessesFactory extends Factory
{
    protected $model = ReportCardProcesses::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $report_card_id = $this->faker->randomElement(\App\Models\ReportCardProcesses::pluck('report_card_id')->toArray()) ?? 1;
            $institution_class_id = $this->faker->randomElement(\App\Models\ReportCardProcesses::pluck('institution_class_id')->toArray()) ?? 1;
            $student_id = $this->faker->randomElement(\App\Models\ReportCardProcesses::pluck('student_id')->toArray()) ?? 1;
    $exists = ReportCardProcesses::where('report_card_id', $report_card_id)
                ->where('institution_class_id', $institution_class_id)
                ->where('student_id', $student_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'report_card_id' => \App\Models\ReportCards::inRandomOrder()->value('id') ?? 1,
    'institution_class_id' => \App\Models\InstitutionClasses::inRandomOrder()->value('id') ?? 1,
    'student_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? 1,
    'status' => $this->faker->numberBetween(1, 1000),
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? 1,
    'education_grade_id' => \App\Models\EducationGrades::inRandomOrder()->value('id') ?? 1,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? 1,
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}