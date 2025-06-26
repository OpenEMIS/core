<?php

namespace Database\Factories;

use App\Models\ScholarshipApplicationInstitutionChoices;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ScholarshipApplicationInstitutionChoicesFactory extends Factory
{
    protected $model = ScholarshipApplicationInstitutionChoices::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'location_type' => $this->faker->lexify(str_repeat("?", 20)),
    'scholarship_institution_choice_type_id' => \App\Models\ScholarshipInstitutionChoiceTypes::inRandomOrder()->value('id') ?? \App\Models\ScholarshipInstitutionChoiceTypes::factory()->create()->id,
    'estimated_cost' => $this->faker->randomFloat(2, 10, 1000),
    'course_name' => $this->faker->lexify(str_repeat("?", 150)),
    'start_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'end_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'is_selected' => $this->faker->numberBetween(0, 1),
    'order' => $this->faker->numberBetween(1, 1000),
    'country_id' => \App\Models\Countries::inRandomOrder()->value('id') ?? \App\Models\Countries::factory()->create()->id,
    'scholarship_institution_choice_status_id' => \App\Models\ScholarshipInstitutionChoiceStatuses::inRandomOrder()->value('id') ?? \App\Models\ScholarshipInstitutionChoiceStatuses::factory()->create()->id,
    'education_field_of_study_id' => \App\Models\EducationFieldOfStudies::inRandomOrder()->value('id') ?? \App\Models\EducationFieldOfStudies::factory()->create()->id,
    'qualification_level_id' => \App\Models\QualificationLevels::inRandomOrder()->value('id') ?? \App\Models\QualificationLevels::factory()->create()->id,
    'applicant_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'scholarship_id' => \App\Models\Scholarships::inRandomOrder()->value('id') ?? \App\Models\Scholarships::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
