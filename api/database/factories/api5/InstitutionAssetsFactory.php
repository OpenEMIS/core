<?php

namespace Database\Factories\Api5;

use App\Models\Api5\InstitutionAssets;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionAssetsFactory extends Factory
{
    protected $model = InstitutionAssets::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'code' => $this->faker->lexify(str_repeat("?", 50)),
    'description' => $this->faker->lexify(str_repeat("?", 250)),
    'asset_make_id' => \App\Models\AssetMakes::inRandomOrder()->value('id') ?? \App\Models\AssetMakes::factory()->create()->id,
    'asset_model_id' => \App\Models\AssetModels::inRandomOrder()->value('id') ?? \App\Models\AssetModels::factory()->create()->id,
    'serial_number' => $this->faker->lexify(str_repeat("?", 50)),
    'purchase_order' => $this->faker->lexify(str_repeat("?", 50)),
    'cost' => $this->faker->randomFloat(2, 10, 1000),
    'stocktake_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'lifespan' => $this->faker->numberBetween(1, 1000),
    'institution_room_id' => \App\Models\InstitutionRooms::inRandomOrder()->value('id') ?? \App\Models\InstitutionRooms::factory()->create()->id,
    'user_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'depreciation' => $this->faker->randomFloat(2, 10, 1000),
    'purchase_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'accessibility' => $this->faker->numberBetween(1, 1000),
    'purpose' => $this->faker->numberBetween(1, 1000),
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'asset_status_id' => \App\Models\AssetStatuses::inRandomOrder()->value('id') ?? \App\Models\AssetStatuses::factory()->create()->id,
    'asset_type_id' => \App\Models\AssetTypes::inRandomOrder()->value('id') ?? \App\Models\AssetTypes::factory()->create()->id,
    'asset_condition_id' => \App\Models\AssetConditions::inRandomOrder()->value('id') ?? \App\Models\AssetConditions::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
