<?php

namespace Database\Factories\Api5;

use App\Models\Api5\InstitutionScanned;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionScannedFactory extends Factory
{
    protected $model = InstitutionScanned::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'openemis_no' => \App\Models\SecurityUsers::inRandomOrder()->value('openemis_no') ?? 1,
    'datetime' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'latitude' => $this->faker->randomFloat(2, 10, 1000),
    'longitude' => $this->faker->randomFloat(2, 10, 1000),
    'access' => $this->faker->lexify(str_repeat("?", 100)),
    'location' => $this->faker->lexify(str_repeat("?", 100)),
    'modified_user_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
