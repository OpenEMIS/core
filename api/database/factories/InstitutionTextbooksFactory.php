<?php

namespace Database\Factories;

use App\Models\InstitutionTextbooks;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionTextbooksFactory extends Factory
{
    protected $model = InstitutionTextbooks::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $id = $this->faker->randomElement(\App\Models\InstitutionTextbooks::pluck('id')->toArray()) ?? 1;
            $academic_period_id = $this->faker->randomElement(\App\Models\InstitutionTextbooks::pluck('academic_period_id')->toArray()) ?? 1;
    $exists = InstitutionTextbooks::where('id', $id)
                ->where('academic_period_id', $academic_period_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'id' => $this->model::max('id') + 1,
    'code' => $this->faker->lexify(str_repeat("?", 100)),
    'comment' => $this->faker->text(50),
    'textbook_status_id' => \App\Models\TextbookStatuses::inRandomOrder()->value('id') ?? 1,
    'textbook_condition_id' => \App\Models\TextbookConditions::inRandomOrder()->value('id') ?? 1,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? 1,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? 1,
    'education_grade_id' => \App\Models\EducationGrades::inRandomOrder()->value('id') ?? 1,
    'education_subject_id' => \App\Models\EducationSubjects::inRandomOrder()->value('id') ?? 1,
    'security_user_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? 1,
    'textbook_id' => \App\Models\Textbooks::inRandomOrder()->value('id') ?? 1,
    'modified_user_id' => $this->faker->numberBetween(1, 1000),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 1000),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
