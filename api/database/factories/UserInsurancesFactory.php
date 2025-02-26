<?php

namespace Database\Factories;

use App\Models\UserInsurances;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class UserInsurancesFactory extends Factory
{
    protected $model = UserInsurances::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'start_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'end_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'insurance_provider_id' => \App\Models\InsuranceProviders::inRandomOrder()->value('id') ?? \App\Models\InsuranceProviders::factory()->create()->id,
    'insurance_type_id' => \App\Models\InsuranceTypes::inRandomOrder()->value('id') ?? \App\Models\InsuranceTypes::factory()->create()->id,
    'security_user_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'comment' => $this->faker->text(50),
    'file_name' => $this->faker->lexify(str_repeat("?", 250)),
    'file_content' => $this->faker->word(),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
