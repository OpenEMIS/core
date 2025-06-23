<?php

namespace Database\Factories\Api5;

use App\Models\Api5\InstitutionCommittees;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionCommitteesFactory extends Factory
{
    protected $model = InstitutionCommittees::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'name' => $this->faker->lexify(str_repeat("?", 50)),
    'chairperson' => $this->faker->lexify(str_repeat("?", 225)),
    'telephone' => $this->faker->numberBetween(1, 1000),
    'email' => $this->faker->lexify(str_repeat("?", 225)),
    'comment' => $this->faker->text(50),
    'institution_committee_type_id' => \App\Models\InstitutionCommitteeTypes::inRandomOrder()->value('id') ?? \App\Models\InstitutionCommitteeTypes::factory()->create()->id,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? \App\Models\AcademicPeriods::factory()->create()->id,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
