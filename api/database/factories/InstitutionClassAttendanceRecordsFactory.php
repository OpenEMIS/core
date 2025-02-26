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
        $attempts = 0;
do {
    $institution_class_id = $this->faker->randomElement(\App\Models\InstitutionClassAttendanceRecords::pluck('institution_class_id')->toArray()) ?? 1;
            $academic_period_id = $this->faker->randomElement(\App\Models\InstitutionClassAttendanceRecords::pluck('academic_period_id')->toArray()) ?? 1;
            $year = $this->faker->randomElement(\App\Models\InstitutionClassAttendanceRecords::pluck('year')->toArray()) ?? 1;
            $month = $this->faker->randomElement(\App\Models\InstitutionClassAttendanceRecords::pluck('month')->toArray()) ?? 1;
    $exists = InstitutionClassAttendanceRecords::where('institution_class_id', $institution_class_id)
                ->where('academic_period_id', $academic_period_id)
                ->where('year', $year)
                ->where('month', $month)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'institution_class_id' => \App\Models\InstitutionClasses::inRandomOrder()->value('id') ?? 1,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? 1,
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