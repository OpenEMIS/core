<?php

namespace Database\Factories\Api5;

use App\Models\Api5\ExaminationCentresExaminations;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ExaminationCentresExaminationsFactory extends Factory
{
    protected $model = ExaminationCentresExaminations::class;

    public function definition(): array
    {

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'total_registered' => $this->faker->numberBetween(1, 1000),
    'examination_centre_id' => \App\Models\ExaminationCentres::factory()->create()->id,
    'examination_id' => \App\Models\Examinations::inRandomOrder()->value('id') ?? \App\Models\Examinations::factory()->create()->id,
// POCOR-8919 removed academic period
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
