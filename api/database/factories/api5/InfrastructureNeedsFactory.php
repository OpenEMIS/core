<?php

namespace Database\Factories\Api5;

use App\Models\Api5\InfrastructureNeeds;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InfrastructureNeedsFactory extends Factory
{
    protected $model = InfrastructureNeeds::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'code' => $this->faker->lexify(str_repeat("?", 50)),
    'name' => $this->faker->lexify(str_repeat("?", 50)),
    'description' => $this->faker->text(50),
    'date_determined' => \Carbon\Carbon::now()->format("Y-m-d"),
    'date_started' => \Carbon\Carbon::now()->format("Y-m-d"),
    'date_completed' => \Carbon\Carbon::now()->format("Y-m-d"),
    'file_name' => $this->faker->lexify(str_repeat("?", 250)),
    'file_content' => $this->faker->word(),
    'comment' => $this->faker->text(50),
    'infrastructure_need_type_id' => \App\Models\InfrastructureNeedTypes::inRandomOrder()->value('id') ?? \App\Models\InfrastructureNeedTypes::factory()->create()->id,
    'priority' => $this->faker->numberBetween(1, 1000),
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
