<?php

namespace Database\Factories;

use App\Models\StaffTrainingNeeds;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StaffTrainingNeedsFactory extends Factory
{
    protected $model = StaffTrainingNeeds::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'reason' => $this->faker->text(50),
    'type' => $this->faker->lexify(str_repeat("?", 20)),
    'training_course_id' => \App\Models\TrainingCourses::inRandomOrder()->value('id') ?? \App\Models\TrainingCourses::factory()->create()->id,
    'training_need_category_id' => \App\Models\TrainingNeedCategories::inRandomOrder()->value('id') ?? \App\Models\TrainingNeedCategories::factory()->create()->id,
    'training_need_competency_id' => \App\Models\TrainingNeedCompetencies::inRandomOrder()->value('id') ?? \App\Models\TrainingNeedCompetencies::factory()->create()->id,
    'training_need_sub_standard_id' => \App\Models\TrainingNeedSubStandards::inRandomOrder()->value('id') ?? \App\Models\TrainingNeedSubStandards::factory()->create()->id,
    'training_priority_id' => \App\Models\TrainingPriorities::inRandomOrder()->value('id') ?? \App\Models\TrainingPriorities::factory()->create()->id,
    'staff_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'assignee_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'status_id' => \App\Models\WorkflowSteps::inRandomOrder()->value('id') ?? \App\Models\WorkflowSteps::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
