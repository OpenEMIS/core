<?php

namespace Database\Factories;

use App\Models\UserBodyMasses;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class UserBodyMassesFactory extends Factory
{
    protected $model = UserBodyMasses::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'height' => $this->faker->randomFloat(2, 10, 1000),
    'weight' => $this->faker->randomFloat(2, 10, 1000),
    'body_mass_index' => $this->faker->randomFloat(2, 10, 1000),
    'comment' => $this->faker->text(50),
    'file_name' => $this->faker->lexify(str_repeat("?", 250)),
    'file_content' => $this->faker->word(),
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? \App\Models\AcademicPeriods::factory()->create()->id,
    'security_user_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
