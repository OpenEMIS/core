<?php

namespace Database\Factories\Api5;

use App\Models\Api5\TrainingCoursesResultTypes;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class TrainingCoursesResultTypesFactory extends Factory
{
    protected $model = TrainingCoursesResultTypes::class;

    public function definition(): array
    {


        return [
    // 'id' => $this->faker->word(),
    'training_course_id' => \App\Models\TrainingCourses::inRandomOrder()->value('id') ?? \App\Models\TrainingCourses::factory()->create()->id,
    'training_result_type_id' => \App\Models\TrainingResultTypes::inRandomOrder()->value('id') ?? \App\Models\TrainingResultTypes::factory()->create()->id,
];
    }
}
