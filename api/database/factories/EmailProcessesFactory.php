<?php

namespace Database\Factories;

use App\Models\EmailProcesses;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class EmailProcessesFactory extends Factory
{
    protected $model = EmailProcesses::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'recipients' => $this->faker->text(50),
    'subject' => $this->faker->text(50),
    'message' => $this->faker->text(50),
    'params' => $this->faker->text(50),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
