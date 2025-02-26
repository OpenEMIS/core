<?php

namespace Database\Factories;

use App\Models\ScholarshipApplicationAttachments;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ScholarshipApplicationAttachmentsFactory extends Factory
{
    protected $model = ScholarshipApplicationAttachments::class;

    public function definition(): array
    {


        return [
    // 'id' => $this->faker->word(),
    'file_name' => $this->faker->lexify(str_repeat("?", 250)),
    'file_content' => $this->faker->word(),
    'scholarship_attachment_type_id' => \App\Models\ScholarshipAttachmentTypes::inRandomOrder()->value('id') ?? \App\Models\ScholarshipAttachmentTypes::factory()->create()->id,
    'applicant_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'scholarship_id' => \App\Models\Scholarships::inRandomOrder()->value('id') ?? \App\Models\Scholarships::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
