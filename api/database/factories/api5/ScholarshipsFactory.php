<?php

namespace Database\Factories\Api5;

use App\Models\Api5\Scholarships;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ScholarshipsFactory extends Factory
{
    protected $model = Scholarships::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'code' => $this->faker->lexify(str_repeat("?", 50)),
    'name' => $this->faker->lexify(str_repeat("?", 250)),
    'description' => $this->faker->text(50),
    'application_open_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'application_close_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'maximum_award_amount' => $this->faker->randomFloat(2, 10, 1000),
    'total_amount' => $this->faker->randomFloat(2, 10, 1000),
    'duration' => $this->faker->numberBetween(1, 1000),
    'bonded_organisation' => $this->faker->lexify(str_repeat("?", 255)),
    'bond' => $this->faker->numberBetween(1, 1000),
    'requirements' => $this->faker->text(50),
    'instructions' => $this->faker->text(50),
    'scholarship_financial_assistance_type_id' => \App\Models\ScholarshipFinancialAssistanceTypes::inRandomOrder()->value('id') ?? \App\Models\ScholarshipFinancialAssistanceTypes::factory()->create()->id,
    'scholarship_financial_assistance_id' => \App\Models\ScholarshipFinancialAssistances::inRandomOrder()->value('id') ?? \App\Models\ScholarshipFinancialAssistances::factory()->create()->id,
    'scholarship_funding_source_id' => \App\Models\ScholarshipFundingSources::inRandomOrder()->value('id') ?? \App\Models\ScholarshipFundingSources::factory()->create()->id,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? \App\Models\AcademicPeriods::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
