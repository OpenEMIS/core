<?php

namespace Database\Factories\Api5;

use App\Models\Api5\InstitutionActivities;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionActivitiesFactory extends Factory
{
    protected $model = InstitutionActivities::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'model' => $this->faker->lexify(str_repeat("?", 200)),
    'model_reference' => $this->faker->numberBetween(1, 1000),
    'field' => $this->faker->lexify(str_repeat("?", 200)),
    'field_type' => $this->faker->lexify(str_repeat("?", 128)),
    'old_value' => $this->faker->lexify(str_repeat("?", 255)),
    'new_value' => $this->faker->lexify(str_repeat("?", 255)),
    'operation' => $this->faker->lexify(str_repeat("?", 10)),
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
