<?php

namespace Database\Factories;

use App\Models\InstitutionTrips;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionTripsFactory extends Factory
{
    protected $model = InstitutionTrips::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'name' => $this->faker->lexify(str_repeat("?", 100)),
    'trip_repeat' => $this->faker->numberBetween(1, 1000),
    'comment' => $this->faker->text(50),
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? \App\Models\AcademicPeriods::factory()->create()->id,
    'trip_type_id' => \App\Models\TripTypes::inRandomOrder()->value('id') ?? \App\Models\TripTypes::factory()->create()->id,
    'institution_transport_provider_id' => \App\Models\InstitutionTransportProviders::inRandomOrder()->value('id') ?? \App\Models\InstitutionTransportProviders::factory()->create()->id,
    'institution_bus_id' => \App\Models\InstitutionBuses::inRandomOrder()->value('id') ?? \App\Models\InstitutionBuses::factory()->create()->id,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
