<?php

namespace Database\Factories;

use App\Models\InstitutionCompetencyPeriodComments;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionCompetencyPeriodCommentsFactory extends Factory
{
    protected $model = InstitutionCompetencyPeriodComments::class;

    public function definition(): array
    {

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'comments' => $this->faker->text(50),
    'student_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'competency_template_id' => \App\Models\CompetencyTemplates::inRandomOrder()->value('id') ?? \App\Models\CompetencyTemplates::factory()->create()->id,
    'competency_period_id' => \App\Models\CompetencyPeriods::inRandomOrder()->value('id') ?? \App\Models\CompetencyPeriods::factory()->create()->id,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? \App\Models\AcademicPeriods::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
