<?php

namespace Database\Factories;

use App\Models\SecurityFunctions;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SecurityFunctionsFactory extends Factory
{
    protected $model = SecurityFunctions::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'name' => $this->faker->lexify(str_repeat("?", 100)),
    'controller' => $this->faker->lexify(str_repeat("?", 100)),
    'module' => $this->faker->lexify(str_repeat("?", 100)),
    'category' => $this->faker->lexify(str_repeat("?", 50)),
    'parent_id' => $this->faker->numberBetween(1, 1000),
    '_view' => $this->faker->text(50),
    '_edit' => $this->faker->text(50),
    '_add' => $this->faker->lexify(str_repeat("?", 200)),
    '_delete' => $this->faker->lexify(str_repeat("?", 200)),
    '_execute' => $this->faker->text(50),
    'order' => $this->faker->numberBetween(1, 1000),
    'visible' => $this->faker->numberBetween(1, 1000),
    'description' => $this->faker->lexify(str_repeat("?", 255)),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
