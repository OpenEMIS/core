<?php

namespace Database\Factories;

use App\Models\InstitutionScheduleCurriculumLessons;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionScheduleCurriculumLessonsFactory extends Factory
{
    protected $model = InstitutionScheduleCurriculumLessons::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'code_only' => $this->faker->numberBetween(1, 1000),
    'institution_schedule_lesson_detail_id' => \App\Models\InstitutionScheduleLessonDetails::inRandomOrder()->value('id') ?? \App\Models\InstitutionScheduleLessonDetails::factory()->create()->id,
    'institution_subject_id' => \App\Models\InstitutionSubjects::inRandomOrder()->value('id') ?? \App\Models\InstitutionSubjects::factory()->create()->id,
];
    }
}
