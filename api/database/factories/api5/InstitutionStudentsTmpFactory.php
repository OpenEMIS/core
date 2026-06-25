<?php

namespace Database\Factories\Api5;

use App\Models\Api5\InstitutionStudentsTmp;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionStudentsTmpFactory extends Factory
{
    protected $model = InstitutionStudentsTmp::class;

    public function definition(): array
    {


        return [
    // 'id' => $this->faker->word(),
    'student_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'start_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
