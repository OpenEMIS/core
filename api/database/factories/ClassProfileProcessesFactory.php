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
        $attempts = 0;
do {
    $class_profile_template_id = $this->faker->randomElement(\App\Models\ClassProfileProcesses::pluck('class_profile_template_id')->toArray()) ?? 1;
            $institution_class_id = $this->faker->randomElement(\App\Models\ClassProfileProcesses::pluck('institution_class_id')->toArray()) ?? 1;
    $exists = ClassProfileProcesses::where('class_profile_template_id', $class_profile_template_id)
                ->where('institution_class_id', $institution_class_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'class_profile_template_id' => \App\Models\ClassProfileTemplates::inRandomOrder()->value('id') ?? \App\Models\ClassProfileTemplates::factory()->create()->id,
    'status' => $this->faker->numberBetween(1, 1000),
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? \App\Models\AcademicPeriods::factory()->create()->id,
    'institution_class_id' => \App\Models\InstitutionClasses::inRandomOrder()->value('id') ?? \App\Models\InstitutionClasses::factory()->create()->id,
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
