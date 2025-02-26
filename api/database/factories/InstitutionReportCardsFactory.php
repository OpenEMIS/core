<?php

namespace Database\Factories;

use App\Models\InstitutionReportCards;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionReportCardsFactory extends Factory
{
    protected $model = InstitutionReportCards::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $report_card_id = $this->faker->randomElement(\App\Models\InstitutionReportCards::pluck('report_card_id')->toArray()) ?? 1;
            $institution_id = $this->faker->randomElement(\App\Models\InstitutionReportCards::pluck('institution_id')->toArray()) ?? 1;
            $academic_period_id = $this->faker->randomElement(\App\Models\InstitutionReportCards::pluck('academic_period_id')->toArray()) ?? 1;
    $exists = InstitutionReportCards::where('report_card_id', $report_card_id)
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
    'report_card_id' => \App\Models\ProfileTemplates::inRandomOrder()->value('id') ?? 1,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? 1,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? 1,
    'modified_user_id' => $this->faker->numberBetween(1, 1000),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 1000),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}