<?php

namespace Database\Factories\Api5;

use App\Models\Api5\ExaminationCentreSpecialNeeds;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ExaminationCentreSpecialNeedsFactory extends Factory
{
    protected $model = ExaminationCentreSpecialNeeds::class;

    public function definition(): array
    {

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'examination_centre_id' =>  \App\Models\ExaminationCentres::factory()->create()->id,
    'special_need_type_id' => \App\Models\SpecialNeedTypes::inRandomOrder()->value('id') ?? \App\Models\SpecialNeedTypes::factory()->create()->id,
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
