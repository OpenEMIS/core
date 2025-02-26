<?php

namespace Database\Factories;

use App\Models\TransferLogs;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class TransferLogsFactory extends Factory
{
    protected $model = TransferLogs::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'features' => $this->faker->lexify(str_repeat("?", 200)),
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? \App\Models\AcademicPeriods::factory()->create()->id,
    'generated_on' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'completed_on' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'generated_by' => $this->faker->numberBetween(1, 1000),
    'process_status' => $this->faker->numberBetween(1, 1000),
    'p_id' => $this->faker->numberBetween(1, 1000),
];
    }
}
