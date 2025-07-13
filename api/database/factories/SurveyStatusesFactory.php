<?php

namespace Database\Factories;

use App\Models\SurveyStatuses;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SurveyStatusesFactory extends Factory
{
    protected $model = SurveyStatuses::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'date_enabled' => \Carbon\Carbon::now()->format("Y-m-d"),
    'date_disabled' => \Carbon\Carbon::now()->format("Y-m-d"),
    'survey_form_id' => \App\Models\SurveyForms::inRandomOrder()->value('id') ?? \App\Models\SurveyForms::factory()->create()->id,
    'survey_filter_id' => $this->faker->numberBetween(1, 1000),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
