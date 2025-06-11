<?php

namespace Database\Factories\Api5;

use App\Models\Api5\InstitutionBusesTransportFeatures;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionBusesTransportFeaturesFactory extends Factory
{
    protected $model = InstitutionBusesTransportFeatures::class;

    public function definition(): array
    {

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'institution_bus_id' =>  \App\Models\InstitutionBuses::factory()->create()->id,
    'transport_feature_id' =>  \App\Models\TransportFeatures::factory()->create()->id,
];
    }
}
