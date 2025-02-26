<?php

namespace Database\Factories;

use App\Models\InstitutionStaffDuties;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionStaffDutiesFactory extends Factory
{
    protected $model = InstitutionStaffDuties::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'staff_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? 1,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? 1,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? 1,
    'staff_duties_id' => \App\Models\StaffDuties::inRandomOrder()->value('id') ?? 1,
    'comment' => $this->faker->text(50),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
