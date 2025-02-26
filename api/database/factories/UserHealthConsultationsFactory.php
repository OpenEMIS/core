<?php

namespace Database\Factories;

use App\Models\UserHealthConsultations;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class UserHealthConsultationsFactory extends Factory
{
    protected $model = UserHealthConsultations::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'description' => $this->faker->text(50),
    'treatment' => $this->faker->text(50),
    'health_consultation_type_id' => \App\Models\HealthConsultationTypes::inRandomOrder()->value('id') ?? 1,
    'security_user_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? 1,
    'file_name' => $this->faker->lexify(str_repeat("?", 250)),
    'file_content' => $this->faker->word(),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
