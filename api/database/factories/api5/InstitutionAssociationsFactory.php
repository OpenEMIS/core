<?php

namespace Database\Factories\Api5;

use App\Models\Api5\InstitutionAssociations;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionAssociationsFactory extends Factory
{
    protected $model = InstitutionAssociations::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'name' => $this->faker->lexify(str_repeat("?", 50)),
    'total_male_students' => $this->faker->numberBetween(1, 1000),
    'total_female_students' => $this->faker->numberBetween(1, 1000),
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? \App\Models\AcademicPeriods::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
