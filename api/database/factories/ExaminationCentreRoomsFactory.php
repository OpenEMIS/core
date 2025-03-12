<?php

namespace Database\Factories;

use App\Models\ExaminationCentreRooms;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ExaminationCentreRoomsFactory extends Factory
{
    protected $model = ExaminationCentreRooms::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'name' => $this->faker->lexify(str_repeat("?", 100)),
    'size' => $this->faker->numberBetween(1, 1000),
    'number_of_seats' => $this->faker->numberBetween(1, 1000),
    'examination_centre_id' => \App\Models\ExaminationCentres::inRandomOrder()->value('id') ?? \App\Models\ExaminationCentres::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
