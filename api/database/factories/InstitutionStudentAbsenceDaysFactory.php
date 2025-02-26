<?php

namespace Database\Factories;

use App\Models\InstitutionStudentAbsenceDays;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionStudentAbsenceDaysFactory extends Factory
{
    protected $model = InstitutionStudentAbsenceDays::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'student_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? 1,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? 1,
    'absence_type_id' => \App\Models\AbsenceTypes::inRandomOrder()->value('id') ?? 1,
    'absent_days' => $this->faker->numberBetween(1, 1000),
    'start_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'end_date' => \Carbon\Carbon::now()->format("Y-m-d"),
];
    }
}
