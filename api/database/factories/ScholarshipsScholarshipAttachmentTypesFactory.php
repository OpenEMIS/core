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

        return [
    'scholarship_id' =>  \App\Models\Scholarships::factory()->create()->id,
    'scholarship_attachment_type_id' => \App\Models\ScholarshipAttachmentTypes::factory()->create()->id,
    'is_mandatory' => $this->faker->numberBetween(0, 1),
];
    }
}
