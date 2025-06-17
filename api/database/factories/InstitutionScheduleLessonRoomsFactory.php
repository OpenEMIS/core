<?php

namespace Database\Factories;

use App\Models\InstitutionScheduleLessonRooms;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionScheduleLessonRoomsFactory extends Factory
{
    protected $model = InstitutionScheduleLessonRooms::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'institution_schedule_lesson_detail_id' => \App\Models\InstitutionScheduleLessonDetails::inRandomOrder()->value('id') ?? \App\Models\InstitutionScheduleLessonDetails::factory()->create()->id,
    'institution_room_id' => \App\Models\InstitutionRooms::inRandomOrder()->value('id') ?? \App\Models\InstitutionRooms::factory()->create()->id,
];
    }
}
