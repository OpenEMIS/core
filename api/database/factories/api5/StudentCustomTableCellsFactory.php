<?php

namespace Database\Factories\Api5;

use App\Models\Api5\StudentCustomTableCells;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StudentCustomTableCellsFactory extends Factory
{
    protected $model = StudentCustomTableCells::class;

    public function definition(): array
    {
        $attempts = 0;


        return [
    'text_value' => $this->faker->lexify(str_repeat("?", 250)),
    'number_value' => $this->faker->numberBetween(1, 1000),
    'decimal_value' => $this->faker->lexify(str_repeat("?", 25)),
    'student_custom_field_id' => \App\Models\StudentCustomFields::factory()->create()->id,
    'student_custom_table_column_id' => \App\Models\StudentCustomTableColumns::factory()->create()->id,
    'student_custom_table_row_id' => \App\Models\StudentCustomTableRows::factory()->create()->id,
    'student_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
