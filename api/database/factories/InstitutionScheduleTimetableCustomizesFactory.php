<?php

namespace Database\Factories;

use App\Models\InstitutionScheduleTimetableCustomizes;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionScheduleTimetableCustomizesFactory extends Factory
{
    protected $model = InstitutionScheduleTimetableCustomizes::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'institution_schedule_timetable_id' => \App\Models\InstitutionScheduleTimetables::inRandomOrder()->value('id') ?? \App\Models\InstitutionScheduleTimetables::factory()->create()->id,
    'customize_key' => $this->faker->lexify(str_repeat("?", 100)),
    'customize_value' => $this->faker->lexify(str_repeat("?", 15)),
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? \App\Models\AcademicPeriods::factory()->create()->id,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
];
    }
}
