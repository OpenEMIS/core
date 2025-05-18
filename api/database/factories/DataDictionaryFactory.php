<?php

namespace Database\Factories;

use App\Models\DataDictionary;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class DataDictionaryFactory extends Factory
{
    protected $model = DataDictionary::class;

    public function definition(): array
    {


        return [
    'database_name' => $this->faker->lexify(str_repeat("?", 200)),
    'table_name' => $this->faker->lexify(str_repeat("?", 200)),
    'table_description' => $this->faker->lexify(str_repeat("?", 200)),
    'primary_keys' => $this->faker->lexify(str_repeat("?", 500)),
    'foreign_keys' => $this->faker->lexify(str_repeat("?", 500)),
    'linked_tables' => $this->faker->lexify(str_repeat("?", 500)),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
