<?php

namespace Database\Factories\Api5;

use App\Models\Api5\LocaleContents;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class LocaleContentsFactory extends Factory
{
    protected $model = LocaleContents::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'en' => $this->faker->text(50),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
