<?php

namespace Database\Factories;

use App\Models\InstitutionClasses;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionClassesFactory extends Factory
{
    protected $model = InstitutionClasses::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'name' => $this->faker->lexify(str_repeat("?", 100)),
    'class_number' => $this->faker->numberBetween(1, 1000),
    'capacity' => $this->faker->numberBetween(1, 1000),
    'total_male_students' => $this->faker->numberBetween(1, 1000),
    'total_female_students' => $this->faker->numberBetween(1, 1000),
    'staff_id' => $this->faker->numberBetween(1, 1000),
    'institution_shift_id' => \App\Models\InstitutionShifts::inRandomOrder()->value('id') ?? 1,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? 1,
    'institution_unit_id' => $this->faker->numberBetween(1, 1000),
    'institution_course_id' => $this->faker->numberBetween(1, 1000),
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? 1,
    'modified_user_id' => $this->faker->numberBetween(1, 1000),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 1000),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
