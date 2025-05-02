<?php

namespace Database\Factories\Api5;

use App\Models\Api5\InstitutionClassSubjects;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionClassSubjectsFactory extends Factory
{
    protected $model = InstitutionClassSubjects::class;

    public function definition(): array
    {


        return [
    // 'id' => $this->faker->word(),
    'status' => $this->faker->numberBetween(1, 1000),
    'institution_class_id' => \App\Models\InstitutionClasses::inRandomOrder()->value('id') ?? \App\Models\InstitutionClasses::factory()->create()->id,
    'institution_subject_id' => \App\Models\InstitutionSubjects::inRandomOrder()->value('id') ?? \App\Models\InstitutionSubjects::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
