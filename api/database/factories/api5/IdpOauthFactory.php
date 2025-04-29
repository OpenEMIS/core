<?php

namespace Database\Factories\Api5;

use App\Models\Api5\IdpOauth;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class IdpOauthFactory extends Factory
{
    protected $model = IdpOauth::class;

    public function definition(): array
    {


        return [
    'system_authentication_id' => \App\Models\SystemAuthentications::factory()->create()->id,
    'client_id' => $this->faker->lexify(str_repeat("?", 150)),
    'client_secret' => $this->faker->lexify(str_repeat("?", 150)),
    'redirect_uri' => $this->faker->lexify(str_repeat("?", 200)),
    'well_known_uri' => $this->faker->lexify(str_repeat("?", 200)),
    'authorization_endpoint' => $this->faker->lexify(str_repeat("?", 200)),
    'token_endpoint' => $this->faker->lexify(str_repeat("?", 200)),
    'userinfo_endpoint' => $this->faker->lexify(str_repeat("?", 200)),
    'issuer' => $this->faker->lexify(str_repeat("?", 200)),
    'jwks_uri' => $this->faker->lexify(str_repeat("?", 200)),
];
    }
}
