<?php

namespace Database\Factories;

use App\Models\InstitutionTripDays;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionTripDaysFactory extends Factory
{
    protected $model = InstitutionTripDays::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $institution_trip_id = $this->faker->randomElement(\App\Models\InstitutionTripDays::pluck('institution_trip_id')->toArray()) ?? 1;
            $day = $this->faker->randomElement(\App\Models\InstitutionTripDays::pluck('day')->toArray()) ?? 1;
    $exists = InstitutionTripDays::where('institution_trip_id', $institution_trip_id)
                ->where('day', $day)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

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
