<?php

namespace Database\Factories;

use App\Models\DataManagementConnections;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class DataManagementConnectionsFactory extends Factory
{
    protected $model = DataManagementConnections::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'name' => $this->faker->lexify(str_repeat("?", 50)),
    'db_type_id' => $this->faker->numberBetween(1, 1000),
    'host' => $this->faker->lexify(str_repeat("?", 100)),
    'host_port' => $this->faker->numberBetween(1, 1000),
    'db_name' => $this->faker->lexify(str_repeat("?", 100)),
    'username' => $this->faker->lexify(str_repeat("?", 50)),
    'password' => $this->faker->text(50),
    'conn_status_id' => $this->faker->numberBetween(1, 1000),
    'status_checked' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
