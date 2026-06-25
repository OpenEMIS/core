<?php

namespace Database\Factories;

use App\Models\SummaryInstitutionRoomTypes;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SummaryInstitutionRoomTypesFactory extends Factory
{
    protected $model = SummaryInstitutionRoomTypes::class;

    public function definition(): array
    {


        return [
    'academic_period_id' => $this->faker->numberBetween(1, 1000),
    'academic_period_name' => $this->faker->lexify(str_repeat("?", 150)),
    'institution_id' => $this->faker->numberBetween(1, 1000),
    'institution_code' => $this->faker->lexify(str_repeat("?", 150)),
    'room_type' => $this->faker->lexify(str_repeat("?", 150)),
    'total_rooms' => $this->faker->numberBetween(1, 1000),
];
    }
}
