<?php

namespace Database\Factories;

use App\Models\StaffReportCardProcesses;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StaffReportCardProcessesFactory extends Factory
{
    protected $model = StaffReportCardProcesses::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $staff_profile_template_id = $this->faker->randomElement(\App\Models\StaffReportCardProcesses::pluck('staff_profile_template_id')->toArray()) ?? 1;
            $staff_id = $this->faker->randomElement(\App\Models\StaffReportCardProcesses::pluck('staff_id')->toArray()) ?? 1;
    $exists = StaffReportCardProcesses::where('staff_profile_template_id', $staff_profile_template_id)
                ->where('staff_id', $staff_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'staff_profile_template_id' => \App\Models\StaffProfileTemplates::inRandomOrder()->value('id') ?? 1,
    'staff_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? 1,
    'status' => $this->faker->numberBetween(1, 1000),
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? 1,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? 1,
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}