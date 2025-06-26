<?php

namespace Database\Factories;

use App\Models\InstitutionClassAttendanceRecords;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionClassAttendanceRecordsFactory extends Factory
{
    protected $model = InstitutionClassAttendanceRecords::class;

    public function definition(): array
    {

        return [
    'institution_class_id' => \App\Models\InstitutionClasses::inRandomOrder()->value('id') ?? \App\Models\InstitutionClasses::factory()->create()->id,
    'academic_period_id' =>  \App\Models\AcademicPeriods::factory()->create()->id,
    'year' => $this->faker->numberBetween(1, 1000),
    'month' => $this->faker->numberBetween(1, 1000),
    'day_1' => $this->faker->numberBetween(1, 1000),
    'day_2' => $this->faker->numberBetween(1, 1000),
    'day_3' => $this->faker->numberBetween(1, 1000),
    'day_4' => $this->faker->numberBetween(1, 1000),
    'day_5' => $this->faker->numberBetween(1, 1000),
    'day_6' => $this->faker->numberBetween(1, 1000),
    'day_7' => $this->faker->numberBetween(1, 1000),
    'day_8' => $this->faker->numberBetween(1, 1000),
    'day_9' => $this->faker->numberBetween(1, 1000),
    'day_10' => $this->faker->numberBetween(1, 1000),
    'day_11' => $this->faker->numberBetween(1, 1000),
    'day_12' => $this->faker->numberBetween(1, 1000),
    'day_13' => $this->faker->numberBetween(1, 1000),
    'day_14' => $this->faker->numberBetween(1, 1000),
    'day_15' => $this->faker->numberBetween(1, 1000),
    'day_16' => $this->faker->numberBetween(1, 1000),
    'day_17' => $this->faker->numberBetween(1, 1000),
    'day_18' => $this->faker->numberBetween(1, 1000),
    'day_19' => $this->faker->numberBetween(1, 1000),
    'day_20' => $this->faker->numberBetween(1, 1000),
    'day_21' => $this->faker->numberBetween(1, 1000),
    'day_22' => $this->faker->numberBetween(1, 1000),
    'day_23' => $this->faker->numberBetween(1, 1000),
    'day_24' => $this->faker->numberBetween(1, 1000),
    'day_25' => $this->faker->numberBetween(1, 1000),
    'day_26' => $this->faker->numberBetween(1, 1000),
    'day_27' => $this->faker->numberBetween(1, 1000),
    'day_28' => $this->faker->numberBetween(1, 1000),
    'day_29' => $this->faker->numberBetween(1, 1000),
    'day_30' => $this->faker->numberBetween(1, 1000),
    'day_31' => $this->faker->numberBetween(1, 1000),
];
    }
}
