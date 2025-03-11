<?php

namespace Database\Factories;

use App\Models\DataManagementCopy;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class DataManagementCopyFactory extends Factory
{
    protected $model = DataManagementCopy::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'from_academic_period' => $this->faker->numberBetween(1, 1000),
    'to_academic_period' => $this->faker->numberBetween(1, 1000),
    'features' => $this->faker->lexify(str_repeat("?", 200)),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d"),
];
    }
}
