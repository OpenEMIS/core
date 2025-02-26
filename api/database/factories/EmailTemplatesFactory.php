<?php

namespace Database\Factories;

use App\Models\EmailTemplates;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class EmailTemplatesFactory extends Factory
{
    protected $model = EmailTemplates::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $model_alias = $this->faker->randomElement(\App\Models\EmailTemplates::pluck('model_alias')->toArray()) ?? 1;
            $model_reference = $this->faker->randomElement(\App\Models\EmailTemplates::pluck('model_reference')->toArray()) ?? 1;
    $exists = EmailTemplates::where('model_alias', $model_alias)
                ->where('model_reference', $model_reference)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

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
