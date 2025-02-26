<?php

namespace Database\Factories;

use App\Models\InstitutionCaseComments;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionCaseCommentsFactory extends Factory
{
    protected $model = InstitutionCaseComments::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'case_id' => \App\Models\InstitutionCases::inRandomOrder()->value('id') ?? 1,
    'comment' => $this->faker->text(50),
    'modified_user_id' => $this->faker->numberBetween(1, 1000),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 1000),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
