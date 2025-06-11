<?php

namespace Database\Factories\Api5;

use App\Models\Api5\MessageRecipients;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class MessageRecipientsFactory extends Factory
{
    protected $model = MessageRecipients::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'message_id' => \App\Models\Messaging::inRandomOrder()->value('id') ?? \App\Models\Messaging::factory()->create()->id,
    'recipient_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
];
    }
}
