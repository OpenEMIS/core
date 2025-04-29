<?php

namespace Database\Factories\Api5;

use App\Models\Api5\InstitutionShifts;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionShiftsFactory extends Factory
{
    protected $model = InstitutionShifts::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'start_time' => $this->faker->word(),
    'end_time' => $this->faker->word(),
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? \App\Models\AcademicPeriods::factory()->create()->id,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'location_institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'shift_option_id' => \App\Models\ShiftOptions::inRandomOrder()->value('id') ?? \App\Models\ShiftOptions::factory()->create()->id,
    'previous_shift_id' => $this->faker->numberBetween(1, 1000),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
