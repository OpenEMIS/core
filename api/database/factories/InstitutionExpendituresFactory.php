<?php

namespace Database\Factories;

use App\Models\InstitutionExpenditures;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionExpendituresFactory extends Factory
{
    protected $model = InstitutionExpenditures::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? 1,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? 1,
    'date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'budget_type_id' => \App\Models\BudgetTypes::inRandomOrder()->value('id') ?? 1,
    'expenditure_type_id' => \App\Models\ExpenditureTypes::inRandomOrder()->value('id') ?? 1,
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
