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
    'case_id' => \App\Models\InstitutionCases::inRandomOrder()->value('id') ?? \App\Models\InstitutionCases::factory()->create()->id,
    'comment' => $this->faker->text(50),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
