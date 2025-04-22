<?php

namespace Database\Factories\Api5;

use App\Models\Api5\ReportCardSubjects;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ReportCardSubjectsFactory extends Factory
{
    protected $model = ReportCardSubjects::class;

    public function definition(): array
    {

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'report_card_id' => \App\Models\ReportCards::inRandomOrder()->value('id') ?? \App\Models\ReportCards::factory()->create()->id,
    'education_subject_id' => \App\Models\EducationSubjects::inRandomOrder()->value('id') ?? \App\Models\EducationSubjects::factory()->create()->id,
    'education_grade_id' => \App\Models\EducationGrades::inRandomOrder()->value('id') ?? \App\Models\EducationGrades::factory()->create()->id,
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
