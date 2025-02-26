<?php

namespace Database\Factories;

use App\Models\InstitutionBuses;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionBusesFactory extends Factory
{
    protected $model = InstitutionBuses::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'plate_number' => $this->faker->lexify(str_repeat("?", 100)),
    'capacity' => $this->faker->numberBetween(1, 1000),
    'comment' => $this->faker->text(50),
    'institution_transport_provider_id' => \App\Models\InstitutionTransportProviders::inRandomOrder()->value('id') ?? 1,
    'bus_type_id' => \App\Models\BusTypes::inRandomOrder()->value('id') ?? 1,
    'transport_status_id' => \App\Models\TransportStatuses::inRandomOrder()->value('id') ?? 1,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? 1,
    'modified_user_id' => $this->faker->numberBetween(1, 1000),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 1000),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
