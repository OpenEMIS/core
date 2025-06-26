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
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? \App\Models\AcademicPeriods::factory()->create()->id,
    'institution_class_id' => \App\Models\InstitutionClasses::inRandomOrder()->value('id') ?? \App\Models\InstitutionClasses::factory()->create()->id,
    'meal_programmes_id' => \App\Models\MealProgrammes::inRandomOrder()->value('id') ?? \App\Models\MealProgrammes::factory()->create()->id,
    'date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'meal_benefit_id' => \App\Models\MealBenefits::inRandomOrder()->value('id') ?? \App\Models\MealBenefits::factory()->create()->id,
];
    }
}
