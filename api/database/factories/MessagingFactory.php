<?php

namespace Database\Factories;

use App\Models\Messaging;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class MessagingFactory extends Factory
{
    protected $model = Messaging::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'message' => $this->faker->lexify(str_repeat("?", 255)),
    'subject' => $this->faker->lexify(str_repeat("?", 255)),
    'recipient_level_id' => $this->faker->numberBetween(1, 1000),
    'recipient_group_id' => $this->faker->lexify(str_repeat("?", 11)),
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? \App\Models\AcademicPeriods::factory()->create()->id,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'status' => $this->faker->numberBetween(1, 1000),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
