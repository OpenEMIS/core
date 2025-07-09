<?php

namespace Database\Factories\Api5;

use App\Models\Api5\StudentGuardians;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StudentGuardiansFactory extends Factory
{
    protected $model = StudentGuardians::class;

    public function definition(): array
    {


        return [
    // 'id' => $this->faker->word(),
    'student_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'guardian_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'guardian_relation_id' => \App\Models\GuardianRelations::inRandomOrder()->value('id') ?? \App\Models\GuardianRelations::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
