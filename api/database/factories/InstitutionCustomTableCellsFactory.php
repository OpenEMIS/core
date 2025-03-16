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

        return [
    'text_value' => $this->faker->lexify(str_repeat("?", 250)),
    'number_value' => $this->faker->numberBetween(1, 1000),
    'decimal_value' => $this->faker->lexify(str_repeat("?", 25)),
    'institution_custom_field_id' => \App\Models\InstitutionCustomFields::inRandomOrder()->value('id') ?? \App\Models\InstitutionCustomFields::factory()->create()->id,
    'institution_custom_table_column_id' => \App\Models\InstitutionCustomTableColumns::inRandomOrder()->value('id') ?? \App\Models\InstitutionCustomTableColumns::factory()->create()->id,
    'institution_custom_table_row_id' => \App\Models\InstitutionCustomTableRows::inRandomOrder()->value('id') ?? \App\Models\InstitutionCustomTableRows::factory()->create()->id,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
