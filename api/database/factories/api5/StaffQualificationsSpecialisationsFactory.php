<?php

namespace Database\Factories\Api5;

use App\Models\Api5\StaffQualificationsSpecialisations;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StaffQualificationsSpecialisationsFactory extends Factory
{
    protected $model = StaffQualificationsSpecialisations::class;

    public function definition(): array
    {

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'staff_qualification_id' => \App\Models\StaffQualifications::factory()->create()->id,
    'qualification_specialisation_id' => \App\Models\QualificationSpecialisations::factory()->create()->id,
];
    }
}
