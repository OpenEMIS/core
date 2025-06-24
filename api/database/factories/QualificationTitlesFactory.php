<?php

namespace Database\Factories;

use App\Models\QualificationTitles;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class QualificationTitlesFactory extends Factory
{
    protected $model = QualificationTitles::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'name' => $this->faker->lexify(str_repeat("?", 250)),
    'order' => $this->faker->numberBetween(1, 1000),
    'visible' => $this->faker->numberBetween(1, 1000),
    'editable' => $this->faker->numberBetween(1, 1000),
    'default' => $this->faker->numberBetween(1, 1000),
    'international_code' => $this->faker->lexify(str_repeat("?", 50)),
    'national_code' => $this->faker->lexify(str_repeat("?", 50)),
    'qualification_level_id' => \App\Models\QualificationLevels::inRandomOrder()->value('id') ?? \App\Models\QualificationLevels::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
