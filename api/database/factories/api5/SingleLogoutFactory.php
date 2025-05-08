<?php

namespace Database\Factories\Api5;

use App\Models\Api5\SingleLogout;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SingleLogoutFactory extends Factory
{
    protected $model = SingleLogout::class;

    public function definition(): array
    {


        return [
    // 'id' => $this->faker->word(),
    'username' => $this->faker->lexify(str_repeat("?", 100)),
    'url' => $this->faker->lexify(str_repeat("?", 255)),
    'session_id' => $this->faker->lexify(str_repeat("?", 50)),
];
    }
}
