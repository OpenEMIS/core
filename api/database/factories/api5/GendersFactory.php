<?php

namespace Database\Factories\Api5;

use App\Models\Api5\Genders;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class GendersFactory extends Factory
{
    protected $model = Genders::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'name' => $this->faker->lexify(str_repeat("?", 10)),
    'code' => $this->faker->lexify(str_repeat("?", 10)),
    'order' => $this->faker->numberBetween(1, 1000),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
