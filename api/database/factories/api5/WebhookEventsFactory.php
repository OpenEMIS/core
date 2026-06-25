<?php

namespace Database\Factories\Api5;

use App\Models\Api5\WebhookEvents;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class WebhookEventsFactory extends Factory
{
    protected $model = WebhookEvents::class;

    public function definition(): array
    {

        return [
            'webhook_id' => \App\Models\Webhooks::factory()->create()->id,
            'event_key' => $this->faker->lexify(str_repeat("?", 45)),
        ];
    }
}
