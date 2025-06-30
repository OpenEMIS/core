<?php

namespace Database\Factories;

use App\Models\OpenemisTemps;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class OpenemisTempsFactory extends Factory
{
    protected $model = OpenemisTemps::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'openemis_no' => $this->faker->lexify(str_repeat("?", 150)),
    'ip_address' => $this->faker->lexify(str_repeat("?", 40)),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
