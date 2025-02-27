<?php

namespace Database\Factories;

use App\Models\StaffCustomTableCells;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StaffCustomTableCellsFactory extends Factory
{
    protected $model = StaffCustomTableCells::class;

    public function definition(): array
    {
        $attempts = 0;


        return [
    'text_value' => $this->faker->lexify(str_repeat("?", 250)),
    'number_value' => $this->faker->numberBetween(1, 1000),
    'decimal_value' => $this->faker->lexify(str_repeat("?", 25)),
    'staff_custom_field_id' => \App\Models\StaffCustomFields::factory()->create()->id,
    'staff_custom_table_column_id' => \App\Models\StaffCustomTableColumns::factory()->create()->id,
    'staff_custom_table_row_id' => \App\Models\StaffCustomTableRows::factory()->create()->id,
    'staff_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
