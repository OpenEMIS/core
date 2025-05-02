<?php

namespace Database\Factories\Api5;

use App\Models\Api5\ScholarshipRecipientActivities;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ScholarshipRecipientActivitiesFactory extends Factory
{
    protected $model = ScholarshipRecipientActivities::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'comments' => $this->faker->text(50),
    'prev_recipient_activity_status_name' => $this->faker->lexify(str_repeat("?", 100)),
    'recipient_activity_status_name' => $this->faker->lexify(str_repeat("?", 100)),
    'recipient_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'scholarship_id' => \App\Models\Scholarships::inRandomOrder()->value('id') ?? \App\Models\Scholarships::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
