<?php

namespace Database\Factories;

use App\Models\StudentMealMarkedRecords;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StudentMealMarkedRecordsFactory extends Factory
{
    protected $model = StudentMealMarkedRecords::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? 1,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? 1,
    'institution_class_id' => \App\Models\InstitutionClasses::inRandomOrder()->value('id') ?? 1,
    'meal_programmes_id' => \App\Models\MealProgrammes::inRandomOrder()->value('id') ?? 1,
    'date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'meal_benefit_id' => \App\Models\MealBenefits::inRandomOrder()->value('id') ?? 1,
];
    }
}
