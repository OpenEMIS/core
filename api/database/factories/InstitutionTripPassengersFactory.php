<?php

namespace Database\Factories;

use App\Models\InstitutionTripPassengers;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionTripPassengersFactory extends Factory
{
    protected $model = InstitutionTripPassengers::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $student_id = $this->faker->randomElement(\App\Models\InstitutionTripPassengers::pluck('student_id')->toArray()) ?? 1;
            $education_grade_id = $this->faker->randomElement(\App\Models\InstitutionTripPassengers::pluck('education_grade_id')->toArray()) ?? 1;
            $academic_period_id = $this->faker->randomElement(\App\Models\InstitutionTripPassengers::pluck('academic_period_id')->toArray()) ?? 1;
            $institution_id = $this->faker->randomElement(\App\Models\InstitutionTripPassengers::pluck('institution_id')->toArray()) ?? 1;
            $institution_trip_id = $this->faker->randomElement(\App\Models\InstitutionTripPassengers::pluck('institution_trip_id')->toArray()) ?? 1;
    $exists = InstitutionTripPassengers::where('student_id', $student_id)
                ->where('education_grade_id', $education_grade_id)
                ->where('academic_period_id', $academic_period_id)
                ->where('institution_id', $institution_id)
                ->where('institution_trip_id', $institution_trip_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'student_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? 1,
    'education_grade_id' => \App\Models\EducationGrades::inRandomOrder()->value('id') ?? 1,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? 1,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? 1,
    'institution_trip_id' => \App\Models\InstitutionTrips::inRandomOrder()->value('id') ?? 1,
    'modified_user_id' => $this->faker->numberBetween(1, 1000),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 1000),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}