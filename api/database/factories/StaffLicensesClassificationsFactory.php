<?php

namespace Database\Factories;

use App\Models\StaffLicensesClassifications;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StaffLicensesClassificationsFactory extends Factory
{
    protected $model = StaffLicensesClassifications::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $staff_license_id = $this->faker->randomElement(\App\Models\StaffLicensesClassifications::pluck('staff_license_id')->toArray()) ?? 1;
            $license_classification_id = $this->faker->randomElement(\App\Models\StaffLicensesClassifications::pluck('license_classification_id')->toArray()) ?? 1;
    $exists = StaffLicensesClassifications::where('staff_license_id', $staff_license_id)
                ->where('license_classification_id', $license_classification_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'staff_license_id' => \App\Models\StaffLicenses::inRandomOrder()->value('id') ?? \App\Models\StaffLicenses::factory()->create()->id,
    'license_classification_id' => \App\Models\LicenseClassifications::inRandomOrder()->value('id') ?? \App\Models\LicenseClassifications::factory()->create()->id,
];
    }
}
