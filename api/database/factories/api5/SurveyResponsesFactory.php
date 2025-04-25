<?php

namespace Database\Factories\Api5;

use App\Models\Api5\SurveyResponses;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SurveyResponsesFactory extends Factory
{
    protected $model = SurveyResponses::class;

    public function definition(): array
    {


        return [
    // 'id' => $this->faker->word(),
    'response' => $this->faker->text(50),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
