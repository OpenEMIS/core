<?php

namespace Database\Factories;

use App\Models\InstitutionStaffAttendances;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionStaffAttendancesFactory extends Factory
{
    protected $model = InstitutionStaffAttendances::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $staff_id = $this->faker->randomElement(\App\Models\InstitutionStaffAttendances::pluck('staff_id')->toArray()) ?? 1;
            $institution_id = $this->faker->randomElement(\App\Models\InstitutionStaffAttendances::pluck('institution_id')->toArray()) ?? 1;
            $academic_period_id = $this->faker->randomElement(\App\Models\InstitutionStaffAttendances::pluck('academic_period_id')->toArray()) ?? 1;
            $date = $this->faker->randomElement(\App\Models\InstitutionStaffAttendances::pluck('date')->toArray()) ?? 1;
    $exists = InstitutionStaffAttendances::where('staff_id', $staff_id)
                ->where('institution_id', $institution_id)
                ->where('academic_period_id', $academic_period_id)
                ->where('date', $date)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'staff_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? \App\Models\AcademicPeriods::factory()->create()->id,
    'date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'time_in' => $this->faker->word(),
    'time_out' => $this->faker->word(),
    'comment' => $this->faker->text(50),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'absence_type_id' => \App\Models\AbsenceTypes::inRandomOrder()->value('id') ?? \App\Models\AbsenceTypes::factory()->create()->id,
];
    }
}
