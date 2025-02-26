<?php

namespace Database\Factories;

use App\Models\UserSpecialNeedsAssessments;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class UserSpecialNeedsAssessmentsFactory extends Factory
{
    protected $model = UserSpecialNeedsAssessments::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'file_name' => $this->faker->lexify(str_repeat("?", 250)),
    'file_content' => $this->faker->word(),
    'comment' => $this->faker->text(50),
    'special_need_type_id' => \App\Models\SpecialNeedTypes::inRandomOrder()->value('id') ?? \App\Models\SpecialNeedTypes::factory()->create()->id,
    'special_need_difficulty_id' => \App\Models\SpecialNeedDifficulties::inRandomOrder()->value('id') ?? \App\Models\SpecialNeedDifficulties::factory()->create()->id,
    'security_user_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'assessor_id' => $this->faker->numberBetween(1, 1000),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
