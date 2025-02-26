<?php

namespace Database\Factories;

use App\Models\ConfigItems;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ConfigItemsFactory extends Factory
{
    protected $model = ConfigItems::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'name' => $this->faker->lexify(str_repeat("?", 100)),
    'code' => $this->faker->lexify(str_repeat("?", 50)),
    'type' => $this->faker->lexify(str_repeat("?", 50)),
    'label' => $this->faker->lexify(str_repeat("?", 100)),
    'value' => $this->faker->lexify(str_repeat("?", 500)),
    'value_selection' => $this->faker->lexify(str_repeat("?", 500)),
    'default_value' => $this->faker->lexify(str_repeat("?", 500)),
    'editable' => $this->faker->numberBetween(1, 1000),
    'visible' => $this->faker->numberBetween(1, 1000),
    'field_type' => $this->faker->lexify(str_repeat("?", 50)),
    'option_type' => $this->faker->lexify(str_repeat("?", 50)),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
