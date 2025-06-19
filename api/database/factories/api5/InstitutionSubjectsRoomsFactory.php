<?php

namespace Database\Factories\Api5;

use App\Models\Api5\InstitutionSubjectsRooms;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionSubjectsRoomsFactory extends Factory
{
    protected $model = InstitutionSubjectsRooms::class;

    public function definition(): array
    {


        return [
    // 'id' => $this->faker->word(),
    'institution_subject_id' => \App\Models\InstitutionSubjects::inRandomOrder()->value('id') ?? \App\Models\InstitutionSubjects::factory()->create()->id,
    'institution_room_id' => \App\Models\InstitutionRooms::inRandomOrder()->value('id') ?? \App\Models\InstitutionRooms::factory()->create()->id,
];
    }
}
