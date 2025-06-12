<?php

namespace Database\Factories;

use App\Models\Locales;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class LocalesFactory extends Factory
{
    protected $model = Locales::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'iso' => $this->faker->lexify(str_repeat("?", 2)),
    'name' => $this->faker->lexify(str_repeat("?", 50)),
    'editable' => $this->faker->numberBetween(1, 1000),
    'direction' => $this->faker->word(),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
