<?php

namespace Database\Factories;

use App\Models\StaffReportCards;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StaffReportCardsFactory extends Factory
{
    protected $model = StaffReportCards::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $staff_profile_template_id = $this->faker->randomElement(\App\Models\StaffReportCards::pluck('staff_profile_template_id')->toArray()) ?? 1;
            $staff_id = $this->faker->randomElement(\App\Models\StaffReportCards::pluck('staff_id')->toArray()) ?? 1;
            $institution_id = $this->faker->randomElement(\App\Models\StaffReportCards::pluck('institution_id')->toArray()) ?? 1;
            $academic_period_id = $this->faker->randomElement(\App\Models\StaffReportCards::pluck('academic_period_id')->toArray()) ?? 1;
    $exists = StaffReportCards::where('staff_profile_template_id', $staff_profile_template_id)
                ->where('staff_id', $staff_id)
                ->where('institution_id', $institution_id)
                ->where('academic_period_id', $academic_period_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'status' => $this->faker->numberBetween(1, 1000),
    'file_name' => $this->faker->lexify(str_repeat("?", 250)),
    'file_content' => $this->faker->word(),
    'file_content_pdf' => $this->faker->word(),
    'started_on' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'completed_on' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'staff_profile_template_id' => \App\Models\StaffProfileTemplates::inRandomOrder()->value('id') ?? 1,
    'staff_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? 1,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? 1,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? 1,
    'modified_user_id' => $this->faker->numberBetween(1, 1000),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 1000),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}