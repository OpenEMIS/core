<?php

namespace Database\Factories;

use App\Models\StaffBehaviours;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StaffBehavioursFactory extends Factory
{
    protected $model = StaffBehaviours::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'description' => $this->faker->text(50),
    'date_of_behaviour' => \Carbon\Carbon::now()->format("Y-m-d"),
    'time_of_behaviour' => $this->faker->word(),
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? 1,
    'staff_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? 1,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? 1,
    'status_id' => $this->faker->numberBetween(1, 1000),
    'staff_behaviour_category_id' => \App\Models\StaffBehaviourCategories::inRandomOrder()->value('id') ?? 1,
    'assignee_id' => $this->faker->numberBetween(1, 1000),
    'behaviour_classification_id' => \App\Models\BehaviourClassifications::inRandomOrder()->value('id') ?? 1,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'action' => $this->faker->text(50),
];
    }
}
