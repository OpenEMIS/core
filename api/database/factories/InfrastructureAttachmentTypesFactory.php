<?php

namespace Database\Factories;

use App\Models\InfrastructureAttachmentTypes;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InfrastructureAttachmentTypesFactory extends Factory
{
    protected $model = InfrastructureAttachmentTypes::class;

    public function definition(): array
    {
        

        return [
    'id' => $this->faker->numberBetween(0, 99999999999),
    'name' => $this->faker->lexify(str_repeat("?", 50)),
    'order' => $this->faker->numberBetween(0, 99999999999),
    'visible' => $this->faker->numberBetween(0, 99999999999),
    'editable' => $this->faker->numberBetween(0, 99999999999),
    'default' => $this->faker->numberBetween(0, 99999999999),
    'international_code' => $this->faker->lexify(str_repeat("?", 50)),
    'national_code' => $this->faker->lexify(str_repeat("?", 50)),
    'modified_user_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? 1,
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? 1,
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}