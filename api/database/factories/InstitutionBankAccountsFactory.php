<?php

namespace Database\Factories;

use App\Models\InstitutionBankAccounts;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionBankAccountsFactory extends Factory
{
    protected $model = InstitutionBankAccounts::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'account_name' => $this->faker->lexify(str_repeat("?", 100)),
    'account_number' => $this->faker->lexify(str_repeat("?", 100)),
    'active' => $this->faker->numberBetween(1, 1000),
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'bank_branch_id' => \App\Models\BankBranches::inRandomOrder()->value('id') ?? \App\Models\BankBranches::factory()->create()->id,
    'remarks' => $this->faker->text(50),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
