<?php

namespace Database\Factories;

use App\Models\ClassProfileProcesses;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ClassProfileProcessesFactory extends Factory
{
    protected $model = ClassProfileProcesses::class;

    public function definition(): array
    {


        return [
    'class_profile_template_id' =>  \App\Models\ClassProfileTemplates::factory()->create()->id,
    'status' => $this->faker->numberBetween(0, 1),
    'institution_id' => \App\Models\Institutions::factory()->create()->id,
    'academic_period_id' => \App\Models\AcademicPeriods::factory()->create()->id,
    'institution_class_id' =>  \App\Models\InstitutionClasses::factory()->create()->id,
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
