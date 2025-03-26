<?php

namespace Database\Factories;

use App\Models\ScholarshipRecipients;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ScholarshipRecipientsFactory extends Factory
{
    protected $model = ScholarshipRecipients::class;

    public function definition(): array
    {

        return [
    'recipient_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'scholarship_id' => \App\Models\Scholarships::inRandomOrder()->value('id') ?? \App\Models\Scholarships::factory()->create()->id,
    'approved_amount' => $this->faker->randomFloat(2, 10, 1000),
    'scholarship_recipient_activity_status_id' => \App\Models\ScholarshipRecipientActivityStatuses::inRandomOrder()->value('id') ?? \App\Models\ScholarshipRecipientActivityStatuses::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
