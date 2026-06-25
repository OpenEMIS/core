<?php

namespace Database\Factories\Api5;

use App\Models\Api5\InstitutionStaffAttendances;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionStaffAttendancesFactory extends Factory
{
    protected $model = InstitutionStaffAttendances::class;

    public function definition(): array
    {

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
