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

        return [
    'workflow_rule_id' => \App\Models\WorkflowRules::inRandomOrder()->value('id') ?? \App\Models\WorkflowRules::factory()->create()->id,
    'event_key' => $this->faker->lexify(str_repeat("?", 45)),
];
    }
}
