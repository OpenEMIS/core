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
        $attempts = 0;
do {
    $custom_field_id = $this->faker->randomElement(\App\Models\CustomTableCells::pluck('custom_field_id')->toArray()) ?? 1;
            $custom_table_column_id = $this->faker->randomElement(\App\Models\CustomTableCells::pluck('custom_table_column_id')->toArray()) ?? 1;
            $custom_table_row_id = $this->faker->randomElement(\App\Models\CustomTableCells::pluck('custom_table_row_id')->toArray()) ?? 1;
            $custom_record_id = $this->faker->randomElement(\App\Models\CustomTableCells::pluck('custom_record_id')->toArray()) ?? 1;
    $exists = CustomTableCells::where('custom_field_id', $custom_field_id)
                ->where('custom_table_column_id', $custom_table_column_id)
                ->where('custom_table_row_id', $custom_table_row_id)
                ->where('custom_record_id', $custom_record_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'text_value' => $this->faker->lexify(str_repeat("?", 250)),
    'number_value' => $this->faker->numberBetween(1, 1000),
    'decimal_value' => $this->faker->lexify(str_repeat("?", 25)),
    'custom_field_id' => \App\Models\CustomFields::inRandomOrder()->value('id') ?? \App\Models\CustomFields::factory()->create()->id,
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
