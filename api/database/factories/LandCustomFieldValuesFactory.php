<?php

namespace Database\Factories;

use App\Models\LandCustomFieldValues;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class LandCustomFieldValuesFactory extends Factory
{
    protected $model = LandCustomFieldValues::class;

    public function definition(): array
    {


        return [
    // 'id' => $this->faker->word(),
    'text_value' => $this->faker->lexify(str_repeat("?", 250)),
    'number_value' => $this->faker->numberBetween(1, 1000),
    'decimal_value' => $this->faker->lexify(str_repeat("?", 25)),
    'textarea_value' => $this->faker->text(50),
    'date_value' => \Carbon\Carbon::now()->format("Y-m-d"),
    'time_value' => $this->faker->word(),
    'file' => $this->faker->word(),
    'infrastructure_custom_field_id' => \App\Models\InfrastructureCustomFields::factory()->create()->id,
    'institution_land_id' => \App\Models\InstitutionLands::inRandomOrder()->value('id') ?? \App\Models\InstitutionLands::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
