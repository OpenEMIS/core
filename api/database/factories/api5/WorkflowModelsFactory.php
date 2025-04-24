<?php

namespace Database\Factories\Api5;

use App\Models\Api5\WorkflowModels;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class WorkflowModelsFactory extends Factory
{
    protected $model = WorkflowModels::class;

    public function definition(): array
    {


        return [
            'id' => $this->model::getNextId(),
            'name' => $this->faker->lexify(str_repeat("?", 100)),
    'model' => $this->faker->lexify(str_repeat("?", 200)),
    'filter' => $this->faker->lexify(str_repeat("?", 200)),
    'is_school_based' => $this->faker->numberBetween(0, 1),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
