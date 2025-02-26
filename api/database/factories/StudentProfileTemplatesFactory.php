<?php

namespace Database\Factories;

use App\Models\StudentProfileTemplates;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StudentProfileTemplatesFactory extends Factory
{
    protected $model = StudentProfileTemplates::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'code' => $this->faker->lexify(str_repeat("?", 50)),
    'name' => $this->faker->lexify(str_repeat("?", 150)),
    'description' => $this->faker->text(50),
    'generate_start_date' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'generate_end_date' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'excel_template_name' => $this->faker->lexify(str_repeat("?", 250)),
    'excel_template' => $this->faker->word(),
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? \App\Models\AcademicPeriods::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
