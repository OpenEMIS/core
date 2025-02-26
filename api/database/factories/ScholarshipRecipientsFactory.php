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
        $attempts = 0;
do {
    $recipient_id = $this->faker->randomElement(\App\Models\ScholarshipRecipients::pluck('recipient_id')->toArray()) ?? 1;
            $scholarship_id = $this->faker->randomElement(\App\Models\ScholarshipRecipients::pluck('scholarship_id')->toArray()) ?? 1;
    $exists = ScholarshipRecipients::where('recipient_id', $recipient_id)
                ->where('scholarship_id', $scholarship_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

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
