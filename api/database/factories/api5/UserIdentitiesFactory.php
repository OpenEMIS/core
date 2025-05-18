<?php

namespace Database\Factories\Api5;

use App\Models\Api5\UserIdentities;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class UserIdentitiesFactory extends Factory
{
    protected $model = UserIdentities::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'identity_type_id' => \App\Models\IdentityTypes::inRandomOrder()->value('id') ?? \App\Models\IdentityTypes::factory()->create()->id,
    'number' => $this->faker->lexify(str_repeat("?", 50)),
    'issue_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'expiry_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'issue_location' => $this->faker->lexify(str_repeat("?", 100)),
    'nationality_id' => \App\Models\Nationalities::inRandomOrder()->value('id') ?? \App\Models\Nationalities::factory()->create()->id,
    'comments' => $this->faker->text(50),
    'preferred' => $this->faker->numberBetween(1, 1000),
    'security_user_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
