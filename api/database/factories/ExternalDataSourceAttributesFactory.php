<?php

namespace Database\Factories;

use App\Models\ExternalDataSourceAttributes;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ExternalDataSourceAttributesFactory extends Factory
{
    protected $model = ExternalDataSourceAttributes::class;

    public function definition(): array
    {


        return [
    'external_data_source_type' => $this->faker->lexify(str_repeat("?", 50)),
    'attribute_field' => $this->faker->lexify(str_repeat("?", 50)),
    'attribute_name' => $this->faker->lexify(str_repeat("?", 100)),
    'value' => $this->faker->text(50),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
];
    }
}
