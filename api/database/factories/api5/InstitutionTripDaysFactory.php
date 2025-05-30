<?php

namespace Database\Factories\Api5;

use App\Models\Api5\InstitutionTripDays;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionTripDaysFactory extends Factory
{
    protected $model = InstitutionTripDays::class;

    public function definition(): array
    {

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'institution_trip_id' => \App\Models\InstitutionTrips::inRandomOrder()->value('id') ?? \App\Models\InstitutionTrips::factory()->create()->id,
    'day' => $this->faker->numberBetween(1, 1000),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
