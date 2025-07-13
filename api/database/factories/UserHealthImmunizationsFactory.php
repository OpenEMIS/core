<?php

namespace Database\Factories;

use App\Models\UserHealthImmunizations;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class UserHealthImmunizationsFactory extends Factory
{
    protected $model = UserHealthImmunizations::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'dosage' => $this->faker->lexify(str_repeat("?", 20)),
    'comment' => $this->faker->text(50),
    'health_immunization_type_id' => \App\Models\HealthImmunizationTypes::inRandomOrder()->value('id') ?? \App\Models\HealthImmunizationTypes::factory()->create()->id,
    'security_user_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'file_name' => $this->faker->lexify(str_repeat("?", 250)),
    'file_content' => $this->faker->word(),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
