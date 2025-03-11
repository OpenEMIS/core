<?php

namespace Database\Factories;

use App\Models\TrainingCoursesPrerequisites;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class TrainingCoursesPrerequisitesFactory extends Factory
{
    protected $model = TrainingCoursesPrerequisites::class;

    public function definition(): array
    {


        return [
    // 'id' => $this->faker->word(),
    'training_course_id' => \App\Models\TrainingCourses::inRandomOrder()->value('id') ?? \App\Models\TrainingCourses::factory()->create()->id,
    'prerequisite_training_course_id' => \App\Models\TrainingCourses::inRandomOrder()->value('id') ?? \App\Models\TrainingCourses::factory()->create()->id,
];
    }
}
