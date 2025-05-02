<?php

namespace Database\Factories\Api5;

use App\Models\Api5\ExaminationCentreRoomsExaminationsStudents;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ExaminationCentreRoomsExaminationsStudentsFactory extends Factory
{
    protected $model = ExaminationCentreRoomsExaminationsStudents::class;

    public function definition(): array
    {

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'examination_centre_room_id' =>  \App\Models\ExaminationCentreRooms::factory()->create()->id,
    'examination_id' => \App\Models\Examinations::inRandomOrder()->value('id') ?? \App\Models\Examinations::factory()->create()->id,
    'student_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'examination_centre_id' => \App\Models\ExaminationCentres::inRandomOrder()->value('id') ?? \App\Models\ExaminationCentres::factory()->create()->id,
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
