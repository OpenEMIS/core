<?php

namespace Database\Factories;

use App\Models\ScholarshipInstitutionChoiceStatuses;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ScholarshipInstitutionChoiceStatusesFactory extends Factory
{
    protected $model = ScholarshipInstitutionChoiceStatuses::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'code' => $this->faker->lexify(str_repeat("?", 100)),
    'name' => $this->faker->lexify(str_repeat("?", 250)),
];
    }
}
