<?php

namespace Database\Factories;

use App\Models\SecurityUserLogins;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SecurityUserLoginsFactory extends Factory
{
    protected $model = SecurityUserLogins::class;

    public function definition(): array
    {

        return [
    'id' => $this->model::max('id') + 1,
    'security_user_id' => $this->faker->numberBetween(1, 1000),
    'login_date_time' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'login_period' => $this->faker->numberBetween(1, 1000),
    'session_id' => $this->faker->lexify(str_repeat("?", 45)),
    'ip_address' => $this->faker->lexify(str_repeat("?", 45)),
];
    }
}
