<?php

namespace Database\Factories;

use App\Models\LocaleContentTranslations;
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
    'locale_content_id' => \App\Models\LocaleContents::inRandomOrder()->value('id') ?? 1,
    'locale_id' => \App\Models\Locales::inRandomOrder()->value('id') ?? 1,
    'modified_user_id' => $this->faker->numberBetween(1, 1000),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 1000),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
