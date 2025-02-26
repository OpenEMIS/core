<?php

namespace Database\Factories;

use App\Models\ScholarshipsScholarshipAttachmentTypes;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ScholarshipsScholarshipAttachmentTypesFactory extends Factory
{
    protected $model = ScholarshipsScholarshipAttachmentTypes::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $scholarship_id = $this->faker->randomElement(\App\Models\ScholarshipsScholarshipAttachmentTypes::pluck('scholarship_id')->toArray()) ?? 1;
            $scholarship_attachment_type_id = $this->faker->randomElement(\App\Models\ScholarshipsScholarshipAttachmentTypes::pluck('scholarship_attachment_type_id')->toArray()) ?? 1;
    $exists = ScholarshipsScholarshipAttachmentTypes::where('scholarship_id', $scholarship_id)
                ->where('scholarship_attachment_type_id', $scholarship_attachment_type_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'scholarship_id' => \App\Models\Scholarships::inRandomOrder()->value('id') ?? 1,
    'scholarship_attachment_type_id' => \App\Models\ScholarshipAttachmentTypes::inRandomOrder()->value('id') ?? 1,
    'is_mandatory' => $this->faker->numberBetween(1, 1000),
];
    }
}