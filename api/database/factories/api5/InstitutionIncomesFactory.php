<?php

namespace Database\Factories\Api5;

use App\Models\Api5\InstitutionIncomes;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionIncomesFactory extends Factory
{
    protected $model = InstitutionIncomes::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? \App\Models\AcademicPeriods::factory()->create()->id,
    'date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'income_source_id' => \App\Models\IncomeSources::inRandomOrder()->value('id') ?? \App\Models\IncomeSources::factory()->create()->id,
    'income_type_id' => \App\Models\IncomeTypes::inRandomOrder()->value('id') ?? \App\Models\IncomeTypes::factory()->create()->id,
    'amount' => $this->faker->numberBetween(1, 1000),
    'file_name' => $this->faker->lexify(str_repeat("?", 250)),
    'file_content' => $this->faker->word(),
    'description' => $this->faker->text(50),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
