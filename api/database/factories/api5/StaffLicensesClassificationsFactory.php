<?php

namespace Database\Factories\Api5;

use App\Models\Api5\StaffLicensesClassifications;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StaffLicensesClassificationsFactory extends Factory
{
    protected $model = StaffLicensesClassifications::class;

    public function definition(): array
    {

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'staff_license_id' => \App\Models\StaffLicenses::inRandomOrder()->value('id') ?? \App\Models\StaffLicenses::factory()->create()->id,
    'license_classification_id' => \App\Models\LicenseClassifications::inRandomOrder()->value('id') ?? \App\Models\LicenseClassifications::factory()->create()->id,
];
    }
}
