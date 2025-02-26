<?php

namespace Database\Factories;

use App\Models\InstitutionMealStudents;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionMealStudentsFactory extends Factory
{
    protected $model = InstitutionMealStudents::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'student_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? 1,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? 1,
    'institution_class_id' => \App\Models\InstitutionClasses::inRandomOrder()->value('id') ?? 1,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? 1,
    'meal_programmes_id' => \App\Models\MealProgrammes::inRandomOrder()->value('id') ?? 1,
    'date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'meal_benefit_id' => \App\Models\MealBenefits::inRandomOrder()->value('id') ?? 1,
    'meal_received_id' => \App\Models\MealReceived::inRandomOrder()->value('id') ?? 1,
    'paid' => $this->faker->randomFloat(2, 10, 1000),
    'comment' => $this->faker->lexify(str_repeat("?", 50)),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
