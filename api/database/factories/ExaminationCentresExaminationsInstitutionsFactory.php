<?php

namespace Database\Factories;

use App\Models\ExaminationCentresExaminationsInstitutions;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ExaminationCentresExaminationsInstitutionsFactory extends Factory
{
    protected $model = ExaminationCentresExaminationsInstitutions::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $examination_centre_id = $this->faker->randomElement(\App\Models\ExaminationCentresExaminationsInstitutions::pluck('examination_centre_id')->toArray()) ?? 1;
            $examination_id = $this->faker->randomElement(\App\Models\ExaminationCentresExaminationsInstitutions::pluck('examination_id')->toArray()) ?? 1;
            $institution_id = $this->faker->randomElement(\App\Models\ExaminationCentresExaminationsInstitutions::pluck('institution_id')->toArray()) ?? 1;
    $exists = ExaminationCentresExaminationsInstitutions::where('examination_centre_id', $examination_centre_id)
                ->where('examination_id', $examination_id)
                ->where('institution_id', $institution_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'examination_centre_id' => \App\Models\ExaminationCentres::inRandomOrder()->value('id') ?? 1,
    'examination_id' => \App\Models\Examinations::inRandomOrder()->value('id') ?? 1,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? 1,
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
