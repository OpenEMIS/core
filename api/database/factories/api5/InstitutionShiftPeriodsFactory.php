<?php

namespace Database\Factories\Api5;

use App\Models\Api5\InstitutionShiftPeriods;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionShiftPeriodsFactory extends Factory
{
    protected $model = InstitutionShiftPeriods::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'institution_shift_period_id' => \App\Models\InstitutionShifts::inRandomOrder()->value('id') ?? \App\Models\InstitutionShifts::factory()->create()->id,
    'period_id' => \App\Models\StudentAttendancePerDayPeriods::inRandomOrder()->value('id') ?? \App\Models\StudentAttendancePerDayPeriods::factory()->create()->id,
];
    }
}
