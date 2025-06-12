<?php

namespace Database\Factories;

use App\Models\TrainingCoursesTargetPopulations;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class TrainingCoursesTargetPopulationsFactory extends Factory
{
    protected $model = TrainingCoursesTargetPopulations::class;

    public function definition(): array
    {


        return [
    // 'id' => $this->faker->word(),
    'training_course_id' => \App\Models\TrainingCourses::inRandomOrder()->value('id') ?? \App\Models\TrainingCourses::factory()->create()->id,
    'target_population_id' => $this->faker->numberBetween(1, 1000),
];
    }
}
