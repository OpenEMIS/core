<?php

namespace Database\Factories\Api5;

use App\Models\Api5\TrainingCourses;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class TrainingCoursesFactory extends Factory
{
    protected $model = TrainingCourses::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'code' => $this->faker->lexify(str_repeat("?", 60)),
    'name' => $this->faker->lexify(str_repeat("?", 250)),
    'description' => $this->faker->text(50),
    'objective' => $this->faker->text(50),
    'credit_hours' => $this->faker->numberBetween(1, 1000),
    'duration' => $this->faker->numberBetween(1, 1000),
    'number_of_months' => $this->faker->numberBetween(1, 1000),
    'special_education_needs' => $this->faker->numberBetween(1, 1000),
    'file_name' => $this->faker->lexify(str_repeat("?", 250)),
    'file_content' => $this->faker->word(),
    'training_field_of_study_id' => \App\Models\TrainingFieldOfStudies::inRandomOrder()->value('id') ?? \App\Models\TrainingFieldOfStudies::factory()->create()->id,
    'training_course_type_id' => \App\Models\TrainingCourseTypes::inRandomOrder()->value('id') ?? \App\Models\TrainingCourseTypes::factory()->create()->id,
    'training_course_category_id' => \App\Models\TrainingCourseCategories::inRandomOrder()->value('id') ?? \App\Models\TrainingCourseCategories::factory()->create()->id,
    'training_mode_of_delivery_id' => \App\Models\TrainingModeDeliveries::inRandomOrder()->value('id') ?? \App\Models\TrainingModeDeliveries::factory()->create()->id,
    'training_requirement_id' => \App\Models\TrainingRequirements::inRandomOrder()->value('id') ?? \App\Models\TrainingRequirements::factory()->create()->id,
    'training_level_id' => \App\Models\TrainingLevels::inRandomOrder()->value('id') ?? \App\Models\TrainingLevels::factory()->create()->id,
    'assignee_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'status_id' => \App\Models\WorkflowSteps::inRandomOrder()->value('id') ?? \App\Models\WorkflowSteps::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
