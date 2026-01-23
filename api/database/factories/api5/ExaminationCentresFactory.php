<?php

namespace Database\Factories\Api5;

use App\Models\Api5\ExaminationCentres;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ExaminationCentresFactory extends Factory
{
    protected $model = ExaminationCentres::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'name' => $this->faker->lexify(str_repeat("?", 150)),
    'code' => $this->faker->lexify(str_repeat("?", 50)),
    'address' => $this->faker->text(50),
    'postal_code' => $this->faker->lexify(str_repeat("?", 20)),
    'contact_person' => $this->faker->lexify(str_repeat("?", 100)),
    'telephone' => $this->faker->lexify(str_repeat("?", 30)),
    'fax' => $this->faker->lexify(str_repeat("?", 30)),
    'email' => $this->faker->lexify(str_repeat("?", 100)),
    'website' => $this->faker->lexify(str_repeat("?", 100)),
    'institution_id' => $this->faker->numberBetween(1, 1000),
    'area_id' => \App\Models\Areas::inRandomOrder()->value('id') ?? \App\Models\Areas::factory()->create()->id,
// POCOR-8919 removed academic period
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
