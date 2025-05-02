<?php

namespace Database\Factories\Api5;

use App\Models\Api5\SummaryInstitutionGrades;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SummaryInstitutionGradesFactory extends Factory
{
    protected $model = SummaryInstitutionGrades::class;

    public function definition(): array
    {


        return [
    'academic_period_id' => $this->faker->numberBetween(1, 1000),
    'academic_period_name' => $this->faker->lexify(str_repeat("?", 150)),
    'institution_id' => $this->faker->numberBetween(1, 1000),
    'institution_code' => $this->faker->lexify(str_repeat("?", 150)),
    'grade_id' => $this->faker->numberBetween(1, 1000),
    'grade_name' => $this->faker->lexify(str_repeat("?", 150)),
    'total_classes' => $this->faker->numberBetween(1, 1000),
    'total_classes_female' => $this->faker->numberBetween(1, 1000),
    'total_classes_male' => $this->faker->numberBetween(1, 1000),
    'total_classes_mixed' => $this->faker->numberBetween(1, 1000),
    'total_students' => $this->faker->numberBetween(1, 1000),
    'total_students_female' => $this->faker->numberBetween(1, 1000),
    'total_students_male' => $this->faker->numberBetween(1, 1000),
    'total_home_room_teachers' => $this->faker->numberBetween(1, 1000),
    'total_secondary_teachers' => $this->faker->numberBetween(1, 1000),
];
    }
}
