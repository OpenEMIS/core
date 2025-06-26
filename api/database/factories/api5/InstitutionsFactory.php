<?php

namespace Database\Factories\Api5;

use App\Models\Api5\Institutions;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionsFactory extends Factory
{
    protected $model = Institutions::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'name' => $this->faker->lexify(str_repeat("?", 150)),
    'alternative_name' => $this->faker->lexify(str_repeat("?", 150)),
    'code' => $this->faker->lexify(str_repeat("?", 20)),
    'address' => $this->faker->text(50),
    'postal_code' => $this->faker->lexify(str_repeat("?", 20)),
    'contact_person' => $this->faker->lexify(str_repeat("?", 100)),
    'telephone' => $this->faker->lexify(str_repeat("?", 30)),
    'fax' => $this->faker->lexify(str_repeat("?", 30)),
    'email' => $this->faker->lexify(str_repeat("?", 100)),
    'website' => $this->faker->lexify(str_repeat("?", 100)),
    'date_opened' => \Carbon\Carbon::now()->format("Y-m-d"),
    'year_opened' => $this->faker->numberBetween(1, 1000),
    'date_closed' => \Carbon\Carbon::now()->format("Y-m-d"),
    'year_closed' => $this->faker->numberBetween(1, 1000),
    'longitude' => $this->faker->lexify(str_repeat("?", 25)),
    'latitude' => $this->faker->lexify(str_repeat("?", 25)),
    'logo_name' => $this->faker->lexify(str_repeat("?", 250)),
    'logo_content' => $this->faker->word(),
    'shift_type' => $this->faker->numberBetween(1, 1000),
    'classification' => $this->faker->numberBetween(1, 1000),
    'area_id' => \App\Models\Areas::inRandomOrder()->value('id') ?? \App\Models\Areas::factory()->create()->id,
    'area_administrative_id' => \App\Models\AreaAdministratives::inRandomOrder()->value('id') ?? \App\Models\AreaAdministratives::factory()->create()->id,
    'institution_locality_id' => \App\Models\InstitutionLocalities::inRandomOrder()->value('id') ?? \App\Models\InstitutionLocalities::factory()->create()->id,
    'institution_type_id' => \App\Models\InstitutionTypes::inRandomOrder()->value('id') ?? \App\Models\InstitutionTypes::factory()->create()->id,
    'institution_ownership_id' => \App\Models\InstitutionOwnerships::inRandomOrder()->value('id') ?? \App\Models\InstitutionOwnerships::factory()->create()->id,
    'institution_status_id' => \App\Models\InstitutionStatuses::inRandomOrder()->value('id') ?? \App\Models\InstitutionStatuses::factory()->create()->id,
    'institution_sector_id' => \App\Models\InstitutionSectors::inRandomOrder()->value('id') ?? \App\Models\InstitutionSectors::factory()->create()->id,
    'institution_provider_id' => \App\Models\InstitutionProviders::inRandomOrder()->value('id') ?? \App\Models\InstitutionProviders::factory()->create()->id,
    'institution_gender_id' => \App\Models\InstitutionGenders::inRandomOrder()->value('id') ?? \App\Models\InstitutionGenders::factory()->create()->id,
    'security_group_id' => \App\Models\SecurityGroups::inRandomOrder()->value('id') ?? \App\Models\SecurityGroups::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
