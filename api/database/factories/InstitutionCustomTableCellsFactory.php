<?php

namespace Database\Factories;

use App\Models\InstitutionCustomTableCells;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionCustomTableCellsFactory extends Factory
{
    protected $model = InstitutionCustomTableCells::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $institution_custom_field_id = $this->faker->randomElement(\App\Models\InstitutionCustomTableCells::pluck('institution_custom_field_id')->toArray()) ?? 1;
            $institution_custom_table_column_id = $this->faker->randomElement(\App\Models\InstitutionCustomTableCells::pluck('institution_custom_table_column_id')->toArray()) ?? 1;
            $institution_custom_table_row_id = $this->faker->randomElement(\App\Models\InstitutionCustomTableCells::pluck('institution_custom_table_row_id')->toArray()) ?? 1;
            $institution_id = $this->faker->randomElement(\App\Models\InstitutionCustomTableCells::pluck('institution_id')->toArray()) ?? 1;
    $exists = InstitutionCustomTableCells::where('institution_custom_field_id', $institution_custom_field_id)
                ->where('institution_custom_table_column_id', $institution_custom_table_column_id)
                ->where('institution_custom_table_row_id', $institution_custom_table_row_id)
                ->where('institution_id', $institution_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'text_value' => $this->faker->lexify(str_repeat("?", 250)),
    'number_value' => $this->faker->numberBetween(1, 1000),
    'decimal_value' => $this->faker->lexify(str_repeat("?", 25)),
    'institution_custom_field_id' => \App\Models\InstitutionCustomFields::inRandomOrder()->value('id') ?? 1,
    'institution_custom_table_column_id' => \App\Models\InstitutionCustomTableColumns::inRandomOrder()->value('id') ?? 1,
    'institution_custom_table_row_id' => \App\Models\InstitutionCustomTableRows::inRandomOrder()->value('id') ?? 1,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? 1,
    'modified_user_id' => $this->faker->numberBetween(1, 1000),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 1000),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}