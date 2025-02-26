<?php

namespace Database\Factories;

use App\Models\ReportCardSubjects;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ReportCardSubjectsFactory extends Factory
{
    protected $model = ReportCardSubjects::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $report_card_id = $this->faker->randomElement(\App\Models\ReportCardSubjects::pluck('report_card_id')->toArray()) ?? 1;
            $education_subject_id = $this->faker->randomElement(\App\Models\ReportCardSubjects::pluck('education_subject_id')->toArray()) ?? 1;
    $exists = ReportCardSubjects::where('report_card_id', $report_card_id)
                ->where('education_subject_id', $education_subject_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'report_card_id' => \App\Models\ReportCards::inRandomOrder()->value('id') ?? 1,
    'education_subject_id' => \App\Models\EducationSubjects::inRandomOrder()->value('id') ?? 1,
    'education_grade_id' => \App\Models\EducationGrades::inRandomOrder()->value('id') ?? 1,
    'created_user_id' => $this->faker->numberBetween(1, 1000),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}