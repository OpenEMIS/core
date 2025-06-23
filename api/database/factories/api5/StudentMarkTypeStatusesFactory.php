<?php

namespace Database\Factories\Api5;

use App\Models\Api5\StudentMarkTypeStatuses;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StudentMarkTypeStatusesFactory extends Factory
{
    protected $model = StudentMarkTypeStatuses::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? \App\Models\AcademicPeriods::factory()->create()->id,
    'student_attendance_mark_type_id' => \App\Models\StudentAttendanceMarkTypes::inRandomOrder()->value('id') ?? \App\Models\StudentAttendanceMarkTypes::factory()->create()->id,
    'date_enabled' => \Carbon\Carbon::now()->format("Y-m-d"),
    'date_disabled' => \Carbon\Carbon::now()->format("Y-m-d"),
];
    }
}
