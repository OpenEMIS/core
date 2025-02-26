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
        $attempts = 0;
do {
    $staff_position_title_id = $this->faker->randomElement(\App\Models\StaffPositionTitlesGrades::pluck('staff_position_title_id')->toArray()) ?? 1;
            $staff_position_grade_id = $this->faker->randomElement(\App\Models\StaffPositionTitlesGrades::pluck('staff_position_grade_id')->toArray()) ?? 1;
    $exists = StaffPositionTitlesGrades::where('staff_position_title_id', $staff_position_title_id)
                ->where('staff_position_grade_id', $staff_position_grade_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'staff_position_title_id' => \App\Models\StaffPositionTitles::inRandomOrder()->value('id') ?? 1,
    'staff_position_grade_id' => $this->faker->numberBetween(1, 1000),
];
    }
}