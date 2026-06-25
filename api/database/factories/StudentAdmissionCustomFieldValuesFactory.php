<?php

namespace Database\Factories;

use App\Models\StudentAdmissionCustomFieldValues;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StudentAdmissionCustomFieldValuesFactory extends Factory
{
    protected $model = StudentAdmissionCustomFieldValues::class;

    public function definition(): array
    {


        return [
    // 'id' => $this->faker->word(),
    'text_value' => $this->faker->lexify(str_repeat("?", 250)),
    'number_value' => $this->faker->numberBetween(1, 1000),
    'decimal_value' => $this->faker->lexify(str_repeat("?", 25)),
    'textarea_value' => $this->faker->text(50),
    'date_value' => \Carbon\Carbon::now()->format("Y-m-d"),
    'time_value' => $this->faker->word(),
    'file' => $this->faker->word(),
    'student_custom_field_id' => \App\Models\StudentCustomFields::factory()->create()->id,
    'institution_student_admission_id' => \App\Models\InstitutionStudentAdmission::factory()->create()->id,
    'modified_user_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
