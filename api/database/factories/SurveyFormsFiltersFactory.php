<?php

namespace Database\Factories;

use App\Models\SurveyFormsFilters;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SurveyFormsFiltersFactory extends Factory
{
    protected $model = SurveyFormsFilters::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'name' => $this->faker->lexify(str_repeat("?", 255)),
    'survey_form_id' => \App\Models\SurveyForms::inRandomOrder()->value('id') ?? \App\Models\SurveyForms::factory()->create()->id,
    'custom_module_id' => $this->faker->numberBetween(1, 1000),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
