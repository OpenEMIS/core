<?php

namespace Database\Factories\Api5;

use App\Models\Api5\InstitutionScheduleNonCurriculumLessons;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionScheduleNonCurriculumLessonsFactory extends Factory
{
    protected $model = InstitutionScheduleNonCurriculumLessons::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'name' => $this->faker->lexify(str_repeat("?", 250)),
    'institution_schedule_lesson_detail_id' => \App\Models\InstitutionScheduleLessonDetails::inRandomOrder()->value('id') ?? \App\Models\InstitutionScheduleLessonDetails::factory()->create()->id,
];
    }
}
