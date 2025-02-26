<?php

namespace Database\Factories;

use App\Models\ClassProfiles;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ClassProfilesFactory extends Factory
{
    protected $model = ClassProfiles::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $class_profile_template_id = $this->faker->randomElement(\App\Models\ClassProfiles::pluck('class_profile_template_id')->toArray()) ?? 1;
            $institution_class_id = $this->faker->randomElement(\App\Models\ClassProfiles::pluck('institution_class_id')->toArray()) ?? 1;
            $institution_id = $this->faker->randomElement(\App\Models\ClassProfiles::pluck('institution_id')->toArray()) ?? 1;
            $academic_period_id = $this->faker->randomElement(\App\Models\ClassProfiles::pluck('academic_period_id')->toArray()) ?? 1;
    $exists = ClassProfiles::where('class_profile_template_id', $class_profile_template_id)
                ->where('institution_class_id', $institution_class_id)
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
    'class_profile_template_id' => \App\Models\ClassProfileTemplates::inRandomOrder()->value('id') ?? \App\Models\ClassProfileTemplates::factory()->create()->id,
    'institution_class_id' => \App\Models\InstitutionClasses::inRandomOrder()->value('id') ?? \App\Models\InstitutionClasses::factory()->create()->id,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? \App\Models\AcademicPeriods::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
