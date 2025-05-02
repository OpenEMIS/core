<?php

namespace Database\Factories\Api5;

use App\Models\Api5\AssessmentItems;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AssessmentItemsFactory extends Factory
{
    protected $model = AssessmentItems::class;

    public function definition(): array
    {


        return [
    // 'id' => $this->faker->word(),
    'weight' => $this->faker->randomFloat(2, 10, 1000),
    'classification' => $this->faker->lexify(str_repeat("?", 250)),
    'assessment_id' => \App\Models\Assessments::inRandomOrder()->value('id') ?? \App\Models\Assessments::factory()->create()->id,
    'education_subject_id' => \App\Models\EducationSubjects::inRandomOrder()->value('id') ?? \App\Models\EducationSubjects::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
