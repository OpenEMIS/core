<?php

namespace Database\Factories\Api5;

use App\Models\Api5\InstitutionCaseLinks;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionCaseLinksFactory extends Factory
{
    protected $model = InstitutionCaseLinks::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'parent_case_id' => $this->faker->numberBetween(1, 1000),
    'child_case_id' => $this->faker->numberBetween(1, 1000),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
