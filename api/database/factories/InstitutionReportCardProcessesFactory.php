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

        return [
    'report_card_id' => \App\Models\ProfileTemplates::inRandomOrder()->value('id') ?? \App\Models\ProfileTemplates::factory()->create()->id,
    'status' => $this->faker->numberBetween(1, 1000),
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? \App\Models\AcademicPeriods::factory()->create()->id,
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
