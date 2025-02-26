<?php

namespace Database\Factories;

use App\Models\InstitutionBusesTransportFeatures;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionBusesTransportFeaturesFactory extends Factory
{
    protected $model = InstitutionBusesTransportFeatures::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $institution_bus_id = $this->faker->randomElement(\App\Models\InstitutionBusesTransportFeatures::pluck('institution_bus_id')->toArray()) ?? 1;
            $transport_feature_id = $this->faker->randomElement(\App\Models\InstitutionBusesTransportFeatures::pluck('transport_feature_id')->toArray()) ?? 1;
    $exists = InstitutionBusesTransportFeatures::where('institution_bus_id', $institution_bus_id)
                ->where('transport_feature_id', $transport_feature_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'institution_bus_id' => \App\Models\InstitutionBuses::inRandomOrder()->value('id') ?? \App\Models\InstitutionBuses::factory()->create()->id,
    'transport_feature_id' => \App\Models\TransportFeatures::inRandomOrder()->value('id') ?? \App\Models\TransportFeatures::factory()->create()->id,
];
    }
}
