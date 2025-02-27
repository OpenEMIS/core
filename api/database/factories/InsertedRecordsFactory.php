<?php

namespace Database\Factories;

use App\Models\InsertedRecords;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InsertedRecordsFactory extends Factory
{
    protected $model = InsertedRecords::class;

    public function definition(): array
    {

        return [
    'id' => $this->model::max('id') + 1,
    'inserted_date' => $this->faker->numberBetween(1, 1000),
    'reference_table' => $this->faker->lexify(str_repeat("?", 50)),
    'reference_key' => $this->faker->text(50),
    'data' => $this->faker->text(50),
    'action_type' => $this->faker->lexify(str_repeat("?", 100)),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
