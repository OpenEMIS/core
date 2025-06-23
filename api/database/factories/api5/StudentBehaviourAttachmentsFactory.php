<?php

namespace Database\Factories\Api5;

use App\Models\Api5\StudentBehaviourAttachments;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StudentBehaviourAttachmentsFactory extends Factory
{
    protected $model = StudentBehaviourAttachments::class;

    public function definition(): array
    {


        return [
    // 'id' => $this->faker->word(),
    'name' => $this->faker->lexify(str_repeat("?", 50)),
    'description' => $this->faker->text(50),
    'file_name' => $this->faker->lexify(str_repeat("?", 250)),
    'file_content' => $this->faker->word(),
    'student_behaviour_id' => \App\Models\StudentBehaviours::inRandomOrder()->value('id') ?? \App\Models\StudentBehaviours::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
