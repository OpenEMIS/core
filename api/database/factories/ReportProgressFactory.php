<?php

namespace Database\Factories;

use App\Models\ReportProgress;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ReportProgressFactory extends Factory
{
    protected $model = ReportProgress::class;

    public function definition(): array
    {


        return [
    // 'id' => $this->faker->word(),
    'name' => $this->faker->lexify(str_repeat("?", 700)),
    'module' => $this->faker->lexify(str_repeat("?", 100)),
    'params' => $this->faker->text(50),
    'sql' => $this->faker->text(50),
    'expiry_date' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'file_path' => $this->faker->lexify(str_repeat("?", 255)),
    'current_records' => $this->faker->numberBetween(1, 1000),
    'total_records' => $this->faker->numberBetween(1, 1000),
    'pid' => $this->faker->numberBetween(1, 1000),
    'status' => $this->faker->numberBetween(1, 1000),
    'error_message' => $this->faker->text(50),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
