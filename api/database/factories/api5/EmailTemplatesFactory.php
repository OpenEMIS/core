<?php

namespace Database\Factories\Api5;

use App\Models\Api5\EmailTemplates;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class EmailTemplatesFactory extends Factory
{
    protected $model = EmailTemplates::class;

    public function definition(): array
    {

        return [
    'model_alias' => $this->faker->lexify(str_repeat("?", 50)),
    'model_reference' => $this->faker->numberBetween(1, 1000),
    'subject' => $this->faker->lexify(str_repeat("?", 255)),
    'message' => $this->faker->text(50),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
