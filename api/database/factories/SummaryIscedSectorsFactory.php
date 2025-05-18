<?php

namespace Database\Factories;

use App\Models\SummaryIscedSectors;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SummaryIscedSectorsFactory extends Factory
{
    protected $model = SummaryIscedSectors::class;

    public function definition(): array
    {


        return [
    'academic_period_id' => $this->faker->numberBetween(1, 1000),
    'academic_period_name' => $this->faker->lexify(str_repeat("?", 150)),
    'institution_sector_id' => $this->faker->numberBetween(1, 1000),
    'institution_sector_name' => $this->faker->lexify(str_repeat("?", 150)),
    'education_system_id' => $this->faker->numberBetween(1, 1000),
    'education_system_name' => $this->faker->lexify(str_repeat("?", 150)),
    'education_level_isced_id' => $this->faker->numberBetween(1, 1000),
    'education_level_isced_name' => $this->faker->lexify(str_repeat("?", 150)),
    'education_level_isced_level' => $this->faker->numberBetween(1, 1000),
    'total_instiutions' => $this->faker->numberBetween(1, 1000),
    'total_electricity_institutions' => $this->faker->numberBetween(1, 1000),
    'total_computer_institutions' => $this->faker->numberBetween(1, 1000),
    'total_teaching_computer_institutions' => $this->faker->numberBetween(1, 1000),
    'total_internet_institutions' => $this->faker->numberBetween(1, 1000),
    'total_toilet_institutions' => $this->faker->numberBetween(1, 1000),
    'total_improved_toilet_institutions' => $this->faker->numberBetween(1, 1000),
    'total_single_sex_toilet_institutions' => $this->faker->numberBetween(1, 1000),
    'total_improved_single_sex_toilet_institutions' => $this->faker->numberBetween(1, 1000),
    'total_in_use_toilet_institutions' => $this->faker->numberBetween(1, 1000),
    'total_in_use_improved_toilet_institutions' => $this->faker->numberBetween(1, 1000),
    'total_in_use_single_sex_toilet_institutions' => $this->faker->numberBetween(1, 1000),
    'total_improved_in_use_single_sex_toilet_institutions' => $this->faker->numberBetween(1, 1000),
    'total_drinking_water_institutions' => $this->faker->numberBetween(1, 1000),
    'total_functional_drinking_water_institutions' => $this->faker->numberBetween(1, 1000),
    'total_handwashing_facility_institutions' => $this->faker->numberBetween(1, 1000),
    'total_accessible_room_institutions' => $this->faker->numberBetween(1, 1000),
];
    }
}
