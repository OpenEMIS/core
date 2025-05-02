<?php

namespace Database\Factories\Api5;

use App\Models\Api5\InstitutionBuses;
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
    'institution_transport_provider_id' => \App\Models\InstitutionTransportProviders::inRandomOrder()->value('id') ?? \App\Models\InstitutionTransportProviders::factory()->create()->id,
    'bus_type_id' => \App\Models\BusTypes::inRandomOrder()->value('id') ?? \App\Models\BusTypes::factory()->create()->id,
    'transport_status_id' => \App\Models\TransportStatuses::inRandomOrder()->value('id') ?? \App\Models\TransportStatuses::factory()->create()->id,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
