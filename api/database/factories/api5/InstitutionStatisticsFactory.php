<?php

namespace Database\Factories\Api5;

use App\Models\Api5\InstitutionStatistics;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionStatisticsFactory extends Factory
{
    protected $model = InstitutionStatistics::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'name' => $this->faker->lexify(str_repeat("?", 150)),
    'query' => $this->faker->text(50),
    'filter' => $this->faker->text(50),
    'conditions' => $this->faker->text(50),
    'excel_template_name' => $this->faker->lexify(str_repeat("?", 250)),
    'excel_template' => $this->faker->word(),
    'format' => $this->faker->numberBetween(1, 1000),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
