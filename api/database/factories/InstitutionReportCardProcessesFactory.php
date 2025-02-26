<?php

namespace Database\Factories;

use App\Models\InstitutionReportCardProcesses;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionReportCardProcessesFactory extends Factory
{
    protected $model = InstitutionReportCardProcesses::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $report_card_id = $this->faker->randomElement(\App\Models\InstitutionReportCardProcesses::pluck('report_card_id')->toArray()) ?? 1;
            $institution_id = $this->faker->randomElement(\App\Models\InstitutionReportCardProcesses::pluck('institution_id')->toArray()) ?? 1;
    $exists = InstitutionReportCardProcesses::where('report_card_id', $report_card_id)
                ->where('institution_id', $institution_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'report_card_id' => \App\Models\ProfileTemplates::inRandomOrder()->value('id') ?? 1,
    'status' => $this->faker->numberBetween(1, 1000),
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? 1,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? 1,
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}