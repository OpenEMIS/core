<?php

namespace Database\Factories\Api5;

use App\Models\Api5\InstitutionStudentAbsenceDays;
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
    'student_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'absence_type_id' => \App\Models\AbsenceTypes::inRandomOrder()->value('id') ?? \App\Models\AbsenceTypes::factory()->create()->id,
    'absent_days' => $this->faker->numberBetween(1, 1000),
    'start_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'end_date' => \Carbon\Carbon::now()->format("Y-m-d"),
];
    }
}
