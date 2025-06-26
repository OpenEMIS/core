<?php

namespace Database\Factories;

use App\Models\InstitutionStaffAttendanceActivities;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionStaffAttendanceActivitiesFactory extends Factory
{
    protected $model = InstitutionStaffAttendanceActivities::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'model' => $this->faker->lexify(str_repeat("?", 200)),
    'model_reference' => $this->faker->lexify(str_repeat("?", 64)),
    'field' => $this->faker->lexify(str_repeat("?", 200)),
    'field_type' => $this->faker->lexify(str_repeat("?", 200)),
    'old_value' => $this->faker->text(50),
    'new_value' => $this->faker->text(50),
    'operation' => $this->faker->lexify(str_repeat("?", 10)),
    'security_user_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
