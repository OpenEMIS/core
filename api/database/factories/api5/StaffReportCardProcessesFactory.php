<?php

namespace Database\Factories\Api5;

use App\Models\Api5\StaffReportCardProcesses;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StaffReportCardProcessesFactory extends Factory
{
    protected $model = StaffReportCardProcesses::class;

    public function definition(): array
    {

        return [
    'staff_profile_template_id' => \App\Models\StaffProfileTemplates::inRandomOrder()->value('id') ?? \App\Models\StaffProfileTemplates::factory()->create()->id,
    'staff_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'status' => $this->faker->numberBetween(1, 1000),
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? \App\Models\AcademicPeriods::factory()->create()->id,
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
