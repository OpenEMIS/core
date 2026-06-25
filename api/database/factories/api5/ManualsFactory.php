<?php

namespace Database\Factories\Api5;

use App\Models\Api5\Manuals;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ManualsFactory extends Factory
{
    protected $model = Manuals::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'function' => $this->faker->lexify(str_repeat("?", 500)),
    'controller' => $this->faker->lexify(str_repeat("?", 50)),
    'module' => $this->faker->lexify(str_repeat("?", 100)),
    'category' => $this->faker->lexify(str_repeat("?", 100)),
    'parent_id' => $this->faker->numberBetween(1, 1000),
    'order' => $this->faker->numberBetween(1, 1000),
    'url' => $this->faker->lexify(str_repeat("?", 255)),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
