<?php

namespace Database\Factories;

use App\Models\WorkflowRuleEvents;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class WorkflowRuleEventsFactory extends Factory
{
    protected $model = WorkflowRuleEvents::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $workflow_rule_id = $this->faker->randomElement(\App\Models\WorkflowRuleEvents::pluck('workflow_rule_id')->toArray()) ?? 1;
            $event_key = $this->faker->randomElement(\App\Models\WorkflowRuleEvents::pluck('event_key')->toArray()) ?? 1;
    $exists = WorkflowRuleEvents::where('workflow_rule_id', $workflow_rule_id)
                ->where('event_key', $event_key)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'workflow_rule_id' => \App\Models\WorkflowRules::inRandomOrder()->value('id') ?? \App\Models\WorkflowRules::factory()->create()->id,
    'event_key' => $this->faker->lexify(str_repeat("?", 45)),
];
    }
}
