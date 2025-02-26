<?php

namespace Database\Factories;

use App\Models\WebhookEvents;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class WebhookEventsFactory extends Factory
{
    protected $model = WebhookEvents::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $webhook_id = $this->faker->randomElement(\App\Models\WebhookEvents::pluck('webhook_id')->toArray()) ?? 1;
            $event_key = $this->faker->randomElement(\App\Models\WebhookEvents::pluck('event_key')->toArray()) ?? 1;
    $exists = WebhookEvents::where('webhook_id', $webhook_id)
                ->where('event_key', $event_key)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'webhook_id' => \App\Models\Webhooks::inRandomOrder()->value('id') ?? 1,
    'event_key' => $this->faker->lexify(str_repeat("?", 45)),
];
    }
}