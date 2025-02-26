<?php

namespace Database\Factories;

use App\Models\DeletedRecords;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class DeletedRecordsFactory extends Factory
{
    protected $model = DeletedRecords::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $id = $this->faker->randomElement(\App\Models\DeletedRecords::pluck('id')->toArray()) ?? 1;
            $deleted_date = $this->faker->randomElement(\App\Models\DeletedRecords::pluck('deleted_date')->toArray()) ?? 1;
    $exists = DeletedRecords::where('id', $id)
                ->where('deleted_date', $deleted_date)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'id' => $this->model::max('id') + 1,
    'reference_table' => $this->faker->lexify(str_repeat("?", 50)),
    'reference_key' => $this->faker->text(50),
    'data' => $this->faker->text(50),
    'deleted_date' => $this->faker->numberBetween(1, 1000),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
