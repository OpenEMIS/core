<?php

namespace Database\Factories;

use App\Models\TrainingCoursesSpecialisations;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class TrainingCoursesSpecialisationsFactory extends Factory
{
    protected $model = TrainingCoursesSpecialisations::class;

    public function definition(): array
    {


        return [
    // 'id' => $this->faker->word(),
    'training_course_id' => \App\Models\TrainingCourses::inRandomOrder()->value('id') ?? \App\Models\TrainingCourses::factory()->create()->id,
    'training_specialisation_id' => \App\Models\TrainingSpecialisations::inRandomOrder()->value('id') ?? \App\Models\TrainingSpecialisations::factory()->create()->id,
];
    }
}
