<?php

namespace Database\Factories\Api5;

use App\Models\Api5\SecurityUserCodes;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SecurityUserCodesFactory extends Factory
{
    protected $model = SecurityUserCodes::class;

    public function definition(): array
    {


        return [
            'id' => $this->model::max('id') + 1,
            'email' => $this->faker->safeEmail,
            'status' => 0,
            'verification_otp' => $this->faker->lexify(str_repeat("?", 200)),
            'expires_at' => \Carbon\Carbon::now()->addHour()->format("Y-m-d H:i:s"),
            'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
        ];

    }
}
