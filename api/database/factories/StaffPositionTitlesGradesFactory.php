<?php

namespace Database\Factories;

use App\Models\StaffPositionTitlesGrades;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StaffPositionTitlesGradesFactory extends Factory
{
    protected $model = StaffPositionTitlesGrades::class;

    public function definition(): array
    {

        return [
    'staff_position_title_id' => \App\Models\StaffPositionTitles::inRandomOrder()->value('id') ?? \App\Models\StaffPositionTitles::factory()->create()->id,
    'staff_position_grade_id' => $this->faker->numberBetween(1, 1000),
];
    }
}
