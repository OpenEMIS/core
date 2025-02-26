<?php

namespace Database\Factories;

use App\Models\UserHealths;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class UserHealthsFactory extends Factory
{
    protected $model = UserHealths::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'blood_type' => $this->faker->lexify(str_repeat("?", 3)),
    'doctor_name' => $this->faker->lexify(str_repeat("?", 150)),
    'doctor_contact' => $this->faker->lexify(str_repeat("?", 11)),
    'medical_facility' => $this->faker->lexify(str_repeat("?", 200)),
    'health_insurance' => $this->faker->numberBetween(1, 1000),
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
