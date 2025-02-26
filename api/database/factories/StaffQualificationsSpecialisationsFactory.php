<?php

namespace Database\Factories;

use App\Models\StaffQualificationsSpecialisations;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StaffQualificationsSpecialisationsFactory extends Factory
{
    protected $model = StaffQualificationsSpecialisations::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $staff_qualification_id = $this->faker->randomElement(\App\Models\StaffQualificationsSpecialisations::pluck('staff_qualification_id')->toArray()) ?? 1;
            $qualification_specialisation_id = $this->faker->randomElement(\App\Models\StaffQualificationsSpecialisations::pluck('qualification_specialisation_id')->toArray()) ?? 1;
    $exists = StaffQualificationsSpecialisations::where('staff_qualification_id', $staff_qualification_id)
                ->where('qualification_specialisation_id', $qualification_specialisation_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'staff_qualification_id' => \App\Models\StaffQualifications::inRandomOrder()->value('id') ?? \App\Models\StaffQualifications::factory()->create()->id,
    'qualification_specialisation_id' => \App\Models\QualificationSpecialisations::inRandomOrder()->value('id') ?? \App\Models\QualificationSpecialisations::factory()->create()->id,
];
    }
}
