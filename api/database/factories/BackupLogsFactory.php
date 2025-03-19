<?php

namespace Database\Factories;

use App\Models\BackupLogs;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class BackupLogsFactory extends Factory
{
    protected $model = BackupLogs::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'name' => $this->faker->lexify(str_repeat("?", 250)),
    'path' => $this->faker->lexify(str_repeat("?", 255)),
    'generated_on' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'generated_by' => $this->faker->numberBetween(1, 1000),
];
    }
}
