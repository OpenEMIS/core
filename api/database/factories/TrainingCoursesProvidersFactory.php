<?php

namespace Database\Factories;

use App\Models\TrainingCoursesProviders;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class TrainingCoursesProvidersFactory extends Factory
{
    protected $model = TrainingCoursesProviders::class;

    public function definition(): array
    {


        return [
    // 'id' => $this->faker->word(),
    'training_course_id' => \App\Models\TrainingCourses::inRandomOrder()->value('id') ?? \App\Models\TrainingCourses::factory()->create()->id,
    'training_provider_id' => \App\Models\TrainingProviders::inRandomOrder()->value('id') ?? \App\Models\TrainingProviders::factory()->create()->id,
];
    }
}
