<?php

namespace Database\Factories;

use App\Models\ExaminationCentresExaminationsInvigilators;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ExaminationCentresExaminationsInvigilatorsFactory extends Factory
{
    protected $model = ExaminationCentresExaminationsInvigilators::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $examination_centre_id = $this->faker->randomElement(\App\Models\ExaminationCentresExaminationsInvigilators::pluck('examination_centre_id')->toArray()) ?? 1;
            $examination_id = $this->faker->randomElement(\App\Models\ExaminationCentresExaminationsInvigilators::pluck('examination_id')->toArray()) ?? 1;
            $invigilator_id = $this->faker->randomElement(\App\Models\ExaminationCentresExaminationsInvigilators::pluck('invigilator_id')->toArray()) ?? 1;
    $exists = ExaminationCentresExaminationsInvigilators::where('examination_centre_id', $examination_centre_id)
                ->where('examination_id', $examination_id)
                ->where('invigilator_id', $invigilator_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'examination_centre_id' => \App\Models\ExaminationCentres::inRandomOrder()->value('id') ?? 1,
    'examination_id' => \App\Models\Examinations::inRandomOrder()->value('id') ?? 1,
    'invigilator_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? 1,
    'created_user_id' => $this->faker->numberBetween(1, 1000),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}