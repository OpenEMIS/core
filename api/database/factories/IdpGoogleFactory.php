<?php

namespace Database\Factories;

use App\Models\IdpGoogle;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class IdpGoogleFactory extends Factory
{
    protected $model = IdpGoogle::class;

    public function definition(): array
    {


        return [
    'system_authentication_id' =>  \App\Models\SystemAuthentications::factory()->create()->id,
    'client_id' => $this->faker->lexify(str_repeat("?", 150)),
    'client_secret' => $this->faker->lexify(str_repeat("?", 150)),
    'redirect_uri' => $this->faker->lexify(str_repeat("?", 150)),
    'hd' => $this->faker->lexify(str_repeat("?", 50)),
];
    }
}
