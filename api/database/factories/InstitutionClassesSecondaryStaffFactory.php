<?php

namespace Database\Factories;

use App\Models\InstitutionClassesSecondaryStaff;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionClassesSecondaryStaffFactory extends Factory
{
    protected $model = InstitutionClassesSecondaryStaff::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $institution_class_id = $this->faker->randomElement(\App\Models\InstitutionClassesSecondaryStaff::pluck('institution_class_id')->toArray()) ?? 1;
            $secondary_staff_id = $this->faker->randomElement(\App\Models\InstitutionClassesSecondaryStaff::pluck('secondary_staff_id')->toArray()) ?? 1;
    $exists = InstitutionClassesSecondaryStaff::where('institution_class_id', $institution_class_id)
                ->where('secondary_staff_id', $secondary_staff_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'institution_class_id' => \App\Models\InstitutionClasses::inRandomOrder()->value('id') ?? \App\Models\InstitutionClasses::factory()->create()->id,
    'secondary_staff_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
