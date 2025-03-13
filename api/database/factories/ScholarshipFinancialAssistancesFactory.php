<?php

namespace Database\Factories;

use App\Models\ScholarshipFinancialAssistances;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ScholarshipFinancialAssistancesFactory extends Factory
{
    protected $model = ScholarshipFinancialAssistances::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'name' => $this->faker->lexify(str_repeat("?", 50)),
    'order' => $this->faker->numberBetween(1, 1000),
    'visible' => $this->faker->numberBetween(1, 1000),
    'editable' => $this->faker->numberBetween(1, 1000),
    'default' => $this->faker->numberBetween(1, 1000),
    'international_code' => $this->faker->lexify(str_repeat("?", 50)),
    'national_code' => $this->faker->lexify(str_repeat("?", 50)),
    'modified_user_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
