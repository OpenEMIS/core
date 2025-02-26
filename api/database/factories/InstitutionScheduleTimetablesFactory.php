<?php

namespace Database\Factories;

use App\Models\InstitutionScheduleTimetables;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionScheduleTimetablesFactory extends Factory
{
    protected $model = InstitutionScheduleTimetables::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'name' => $this->faker->lexify(str_repeat("?", 250)),
    'status' => $this->faker->numberBetween(1, 1000),
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? 1,
    'institution_class_id' => \App\Models\InstitutionClasses::inRandomOrder()->value('id') ?? 1,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? 1,
    'institution_schedule_interval_id' => \App\Models\InstitutionScheduleIntervals::inRandomOrder()->value('id') ?? 1,
    'institution_schedule_term_id' => \App\Models\InstitutionScheduleTerms::inRandomOrder()->value('id') ?? 1,
    'modified_user_id' => $this->faker->numberBetween(1, 1000),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 1000),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
