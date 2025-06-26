<?php

namespace Database\Factories;

use App\Models\UserContacts;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class UserContactsFactory extends Factory
{
    protected $model = UserContacts::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'contact_type_id' => \App\Models\ContactTypes::inRandomOrder()->value('id') ?? \App\Models\ContactTypes::factory()->create()->id,
    'value' => $this->faker->lexify(str_repeat("?", 100)),
    'preferred' => $this->faker->numberBetween(1, 1000),
    'security_user_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
