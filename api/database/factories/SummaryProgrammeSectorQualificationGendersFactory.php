<?php

namespace Database\Factories;

use App\Models\SummaryProgrammeSectorQualificationGenders;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SummaryProgrammeSectorQualificationGendersFactory extends Factory
{
    protected $model = SummaryProgrammeSectorQualificationGenders::class;

    public function definition(): array
    {


        return [
    'academic_period_id' => $this->faker->numberBetween(1, 1000),
    'academic_period_name' => $this->faker->lexify(str_repeat("?", 200)),
    'institution_sector_id' => $this->faker->numberBetween(1, 1000),
    'institution_sector_name' => $this->faker->lexify(str_repeat("?", 200)),
    'education_system_id' => $this->faker->numberBetween(1, 1000),
    'education_system_name' => $this->faker->lexify(str_repeat("?", 200)),
    'education_level_isced_id' => $this->faker->numberBetween(1, 1000),
    'education_level_isced_name' => $this->faker->lexify(str_repeat("?", 200)),
    'education_level_isced_level' => $this->faker->numberBetween(1, 1000),
    'education_level_id' => $this->faker->numberBetween(1, 1000),
    'education_level_name' => $this->faker->lexify(str_repeat("?", 200)),
    'education_cycle_id' => $this->faker->numberBetween(1, 1000),
    'education_cycle_name' => $this->faker->lexify(str_repeat("?", 200)),
    'education_programme_id' => $this->faker->numberBetween(1, 1000),
    'education_programme_code' => $this->faker->lexify(str_repeat("?", 200)),
    'education_programme_name' => $this->faker->lexify(str_repeat("?", 200)),
    'staff_gender_id' => $this->faker->numberBetween(1, 1000),
    'staff_gender_name' => $this->faker->lexify(str_repeat("?", 200)),
    'staff_qualification_title_id' => $this->faker->numberBetween(1, 1000),
    'staff_qualification_title_name' => $this->faker->lexify(str_repeat("?", 200)),
    'total_staff_teaching' => $this->faker->numberBetween(1, 1000),
    'total_staff_teaching_newly_recruited' => $this->faker->numberBetween(1, 1000),
];
    }
}
