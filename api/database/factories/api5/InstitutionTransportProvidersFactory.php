<?php

namespace Database\Factories\Api5;

use App\Models\Api5\InstitutionTransportProviders;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionTransportProvidersFactory extends Factory
{
    protected $model = InstitutionTransportProviders::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'name' => $this->faker->lexify(str_repeat("?", 100)),
    'address' => $this->faker->text(50),
    'email' => $this->faker->lexify(str_repeat("?", 100)),
    'contact_number' => $this->faker->lexify(str_repeat("?", 15)),
    'registration_number' => $this->faker->lexify(str_repeat("?", 50)),
    'comment' => $this->faker->text(50),
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
