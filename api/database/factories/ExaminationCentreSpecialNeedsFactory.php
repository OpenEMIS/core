<?php

namespace Database\Factories;

use App\Models\ExaminationCentreSpecialNeeds;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ExaminationCentreSpecialNeedsFactory extends Factory
{
    protected $model = ExaminationCentreSpecialNeeds::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $examination_centre_id = $this->faker->randomElement(\App\Models\ExaminationCentreSpecialNeeds::pluck('examination_centre_id')->toArray()) ?? 1;
            $special_need_type_id = $this->faker->randomElement(\App\Models\ExaminationCentreSpecialNeeds::pluck('special_need_type_id')->toArray()) ?? 1;
    $exists = ExaminationCentreSpecialNeeds::where('examination_centre_id', $examination_centre_id)
                ->where('special_need_type_id', $special_need_type_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'examination_centre_id' => \App\Models\ExaminationCentres::inRandomOrder()->value('id') ?? 1,
    'special_need_type_id' => \App\Models\SpecialNeedTypes::inRandomOrder()->value('id') ?? 1,
    'created_user_id' => $this->faker->numberBetween(1, 1000),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}