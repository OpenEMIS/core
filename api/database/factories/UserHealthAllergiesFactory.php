<?php

namespace Database\Factories;

use App\Models\UserHealthAllergies;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class UserHealthAllergiesFactory extends Factory
{
    protected $model = UserHealthAllergies::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'description' => $this->faker->lexify(str_repeat("?", 200)),
    'severe' => $this->faker->numberBetween(1, 1000),
    'comment' => $this->faker->text(50),
    'health_allergy_type_id' => \App\Models\HealthAllergyTypes::inRandomOrder()->value('id') ?? 1,
    'security_user_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? 1,
    'file_name' => $this->faker->lexify(str_repeat("?", 250)),
    'file_content' => $this->faker->word(),
    'modified_user_id' => $this->faker->numberBetween(1, 1000),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 1000),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
