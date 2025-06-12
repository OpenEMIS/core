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

        return [
    'report_card_id' => \App\Models\ReportCards::inRandomOrder()->value('id') ?? \App\Models\ReportCards::factory()->create()->id,
    'institution_class_id' => \App\Models\InstitutionClasses::inRandomOrder()->value('id') ?? \App\Models\InstitutionClasses::factory()->create()->id,
    'student_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'status' => $this->faker->numberBetween(1, 1000),
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'education_grade_id' => \App\Models\EducationGrades::inRandomOrder()->value('id') ?? \App\Models\EducationGrades::factory()->create()->id,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? \App\Models\AcademicPeriods::factory()->create()->id,
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
