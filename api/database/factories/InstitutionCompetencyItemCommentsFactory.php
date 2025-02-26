<?php

namespace Database\Factories;

use App\Models\InstitutionCompetencyItemComments;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionCompetencyItemCommentsFactory extends Factory
{
    protected $model = InstitutionCompetencyItemComments::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $student_id = $this->faker->randomElement(\App\Models\InstitutionCompetencyItemComments::pluck('student_id')->toArray()) ?? 1;
            $competency_template_id = $this->faker->randomElement(\App\Models\InstitutionCompetencyItemComments::pluck('competency_template_id')->toArray()) ?? 1;
            $competency_period_id = $this->faker->randomElement(\App\Models\InstitutionCompetencyItemComments::pluck('competency_period_id')->toArray()) ?? 1;
            $competency_item_id = $this->faker->randomElement(\App\Models\InstitutionCompetencyItemComments::pluck('competency_item_id')->toArray()) ?? 1;
            $institution_id = $this->faker->randomElement(\App\Models\InstitutionCompetencyItemComments::pluck('institution_id')->toArray()) ?? 1;
            $academic_period_id = $this->faker->randomElement(\App\Models\InstitutionCompetencyItemComments::pluck('academic_period_id')->toArray()) ?? 1;
    $exists = InstitutionCompetencyItemComments::where('student_id', $student_id)
                ->where('competency_template_id', $competency_template_id)
                ->where('competency_period_id', $competency_period_id)
                ->where('competency_item_id', $competency_item_id)
                ->where('institution_id', $institution_id)
                ->where('academic_period_id', $academic_period_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'comments' => $this->faker->text(50),
    'student_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? 1,
    'competency_template_id' => \App\Models\CompetencyTemplates::inRandomOrder()->value('id') ?? 1,
    'competency_period_id' => \App\Models\CompetencyPeriods::inRandomOrder()->value('id') ?? 1,
    'competency_item_id' => \App\Models\CompetencyItems::inRandomOrder()->value('id') ?? 1,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? 1,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? 1,
    'modified_user_id' => $this->faker->numberBetween(1, 1000),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 1000),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}