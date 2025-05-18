<?php

namespace Database\Factories\Api5;

use App\Models\Api5\ApiCredentials;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ApiCredentialsFactory extends Factory
{
    protected $model = ApiCredentials::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'name' => $this->faker->lexify(str_repeat("?", 100)),
    'client_id' => $this->faker->lexify(str_repeat("?", 100)),
    'public_key' => $this->faker->text(50),
    'api_key' => $this->faker->lexify(str_repeat("?", 200)),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
