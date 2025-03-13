<?php

namespace Database\Factories;

use App\Models\CustomTableCells;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class CustomTableCellsFactory extends Factory
{
    protected $model = CustomTableCells::class;

    public function definition(): array
    {

        return [
    'text_value' => $this->faker->lexify(str_repeat("?", 250)),
    'number_value' => $this->faker->numberBetween(1, 1000),
    'decimal_value' => $this->faker->lexify(str_repeat("?", 25)),
    'custom_field_id' =>  \App\Models\CustomFields::factory()->create()->id,
    'custom_table_column_id' => \App\Models\CustomTableColumns::inRandomOrder()->value('id') ?? \App\Models\CustomTableColumns::factory()->create()->id,
    'custom_table_row_id' => \App\Models\CustomTableRows::inRandomOrder()->value('id') ?? \App\Models\CustomTableRows::factory()->create()->id,
    'custom_record_id' => \App\Models\CustomRecords::inRandomOrder()->value('id') ?? \App\Models\CustomRecords::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
