<?php

namespace Database\Factories;

use App\Models\SummaryInstitutions;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SummaryInstitutionsFactory extends Factory
{
    protected $model = SummaryInstitutions::class;

    public function definition(): array
    {


        return [
    'academic_period_id' => $this->faker->numberBetween(1, 1000),
    'academic_period_name' => $this->faker->lexify(str_repeat("?", 150)),
    'institution_id' => $this->faker->numberBetween(1, 1000),
    'institution_code' => $this->faker->lexify(str_repeat("?", 150)),
    'total_grades' => $this->faker->numberBetween(1, 1000),
    'total_classes' => $this->faker->numberBetween(1, 1000),
    'total_lands' => $this->faker->numberBetween(1, 1000),
    'total_land_size' => $this->faker->numberBetween(1, 1000),
    'total_buildings' => $this->faker->numberBetween(1, 1000),
    'total_building_sizes' => $this->faker->numberBetween(1, 1000),
    'total_floors' => $this->faker->numberBetween(1, 1000),
    'total_floor_sizes' => $this->faker->numberBetween(1, 1000),
    'total_rooms' => $this->faker->numberBetween(1, 1000),
    'total_room_sizes' => $this->faker->numberBetween(1, 1000),
    'total_room_classrooms' => $this->faker->numberBetween(1, 1000),
    'total_room_classroom_sizes' => $this->faker->numberBetween(1, 1000),
    'total_students' => $this->faker->numberBetween(1, 1000),
    'total_students_female' => $this->faker->numberBetween(1, 1000),
    'total_students_male' => $this->faker->numberBetween(1, 1000),
    'total_staff_teaching' => $this->faker->numberBetween(1, 1000),
    'total_staff_teaching_female' => $this->faker->numberBetween(1, 1000),
    'total_staff_teaching_male' => $this->faker->numberBetween(1, 1000),
    'total_staff_non_teaching' => $this->faker->numberBetween(1, 1000),
    'total_staff_non_teaching_female' => $this->faker->numberBetween(1, 1000),
    'total_staff_non_teaching_male' => $this->faker->numberBetween(1, 1000),
];
    }
}
