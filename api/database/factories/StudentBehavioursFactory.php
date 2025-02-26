<?php

namespace Database\Factories;

use App\Models\StudentBehaviours;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StudentBehavioursFactory extends Factory
{
    protected $model = StudentBehaviours::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'description' => $this->faker->text(50),
    'action' => $this->faker->text(50),
    'date_of_behaviour' => \Carbon\Carbon::now()->format("Y-m-d"),
    'time_of_behaviour' => $this->faker->word(),
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? 1,
    'student_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? 1,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? 1,
    'status_id' => $this->faker->numberBetween(1, 1000),
    'student_behaviour_category_id' => \App\Models\StudentBehaviourCategories::inRandomOrder()->value('id') ?? 1,
    'assignee_id' => $this->faker->numberBetween(1, 1000),
    'modified_user_id' => $this->faker->numberBetween(1, 1000),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 1000),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'student_behaviour_classification_id' => \App\Models\StudentBehaviourClassifications::inRandomOrder()->value('id') ?? 1,
];
    }
}
