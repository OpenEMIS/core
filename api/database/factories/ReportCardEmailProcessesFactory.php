<?php

namespace Database\Factories;

use App\Models\ReportCardEmailProcesses;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ReportCardEmailProcessesFactory extends Factory
{
    protected $model = ReportCardEmailProcesses::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $report_card_id = $this->faker->randomElement(\App\Models\ReportCardEmailProcesses::pluck('report_card_id')->toArray()) ?? 1;
            $institution_class_id = $this->faker->randomElement(\App\Models\ReportCardEmailProcesses::pluck('institution_class_id')->toArray()) ?? 1;
            $student_id = $this->faker->randomElement(\App\Models\ReportCardEmailProcesses::pluck('student_id')->toArray()) ?? 1;
    $exists = ReportCardEmailProcesses::where('report_card_id', $report_card_id)
                ->where('institution_class_id', $institution_class_id)
                ->where('student_id', $student_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'report_card_id' => \App\Models\ReportCards::inRandomOrder()->value('id') ?? \App\Models\ReportCards::factory()->create()->id,
    'institution_class_id' => \App\Models\InstitutionClasses::inRandomOrder()->value('id') ?? \App\Models\InstitutionClasses::factory()->create()->id,
    'student_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'status' => $this->faker->numberBetween(1, 1000),
    'error_message' => $this->faker->text(50),
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'education_grade_id' => \App\Models\EducationGrades::inRandomOrder()->value('id') ?? \App\Models\EducationGrades::factory()->create()->id,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? \App\Models\AcademicPeriods::factory()->create()->id,
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
