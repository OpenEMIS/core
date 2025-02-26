<?php

namespace Database\Factories;

use App\Models\ScholarshipApplications;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ScholarshipApplicationsFactory extends Factory
{
    protected $model = ScholarshipApplications::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $applicant_id = $this->faker->randomElement(\App\Models\ScholarshipApplications::pluck('applicant_id')->toArray()) ?? 1;
            $scholarship_id = $this->faker->randomElement(\App\Models\ScholarshipApplications::pluck('scholarship_id')->toArray()) ?? 1;
    $exists = ScholarshipApplications::where('applicant_id', $applicant_id)
                ->where('scholarship_id', $scholarship_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'applicant_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? 1,
    'scholarship_id' => \App\Models\Scholarships::inRandomOrder()->value('id') ?? 1,
    'requested_amount' => $this->faker->randomFloat(2, 10, 1000),
    'comments' => $this->faker->text(50),
    'status_id' => \App\Models\WorkflowSteps::inRandomOrder()->value('id') ?? 1,
    'assignee_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? 1,
    'modified_user_id' => $this->faker->numberBetween(1, 1000),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 1000),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}