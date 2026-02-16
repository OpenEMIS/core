<?php

namespace Database\Factories\Api5;

use App\Models\Api5\LocaleContentTranslations;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class LocaleContentTranslationsFactory extends Factory
{
    protected $model = LocaleContentTranslations::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'translation' => $this->faker->text(50),
    'locale_content_id' => \App\Models\LocaleContents::inRandomOrder()->value('id') ?? \App\Models\LocaleContents::factory()->create()->id,
    'locale_id' => \App\Models\Locales::inRandomOrder()->value('id') ?? \App\Models\Locales::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
