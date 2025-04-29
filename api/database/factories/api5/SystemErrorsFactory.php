<?php

namespace Database\Factories\Api5;

use App\Models\Api5\SystemErrors;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SystemErrorsFactory extends Factory
{
    protected $model = SystemErrors::class;

    public function definition(): array
    {


        return [
    // 'id' => $this->faker->word(),
    'code' => $this->faker->lexify(str_repeat("?", 10)),
    'error_message' => $this->faker->text(50),
    'request_method' => $this->faker->lexify(str_repeat("?", 10)),
    'request_url' => $this->faker->text(50),
    'referrer_url' => $this->faker->text(50),
    'client_ip' => $this->faker->lexify(str_repeat("?", 50)),
    'client_browser' => $this->faker->text(50),
    'triggered_from' => $this->faker->text(50),
    'stack_trace' => $this->faker->text(50),
    'server_info' => $this->faker->text(50),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
