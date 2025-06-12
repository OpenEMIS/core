<?php

namespace Database\Factories\Api5;

use App\Models\Api5\InstitutionBuildings;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionBuildingsFactory extends Factory
{
    protected $model = InstitutionBuildings::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'code' => $this->faker->lexify(str_repeat("?", 100)),
    'name' => $this->faker->lexify(str_repeat("?", 250)),
    'start_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'start_year' => $this->faker->numberBetween(1, 1000),
    'end_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'end_year' => $this->faker->numberBetween(1, 1000),
    'year_acquired' => $this->faker->numberBetween(1, 1000),
    'year_disposed' => $this->faker->numberBetween(1, 1000),
    'area' => $this->faker->randomFloat(2, 10, 1000),
    'accessibility' => $this->faker->numberBetween(1, 1000),
    'comment' => $this->faker->text(50),
    'institution_land_id' => $this->faker->numberBetween(1, 1000),
    'institution_id' => $this->faker->numberBetween(1, 1000),
    'building_type_id' => $this->faker->numberBetween(1, 1000),
    'building_status_id' => $this->faker->numberBetween(1, 1000),
    'infrastructure_ownership_id' => $this->faker->numberBetween(1, 1000),
    'infrastructure_condition_id' => $this->faker->numberBetween(1, 1000),
    'previous_institution_building_id' => $this->faker->numberBetween(1, 1000),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
