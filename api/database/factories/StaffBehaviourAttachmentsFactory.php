<?php

namespace Database\Factories;

use App\Models\StaffBehaviourAttachments;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StaffBehaviourAttachmentsFactory extends Factory
{
    protected $model = StaffBehaviourAttachments::class;

    public function definition(): array
    {


        return [
    // 'id' => $this->faker->word(),
    'name' => $this->faker->lexify(str_repeat("?", 50)),
    'description' => $this->faker->text(50),
    'file_name' => $this->faker->lexify(str_repeat("?", 250)),
    'file_content' => $this->faker->word(),
    'staff_behaviour_id' => \App\Models\StaffBehaviours::inRandomOrder()->value('id') ?? \App\Models\StaffBehaviours::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
