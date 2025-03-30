<?php

namespace Database\Factories;

use App\Models\UserComments;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class UserCommentsFactory extends Factory
{
    protected $model = UserComments::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'title' => $this->faker->lexify(str_repeat("?", 100)),
    'comment' => $this->faker->text(50),
    'comment_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'comment_type_id' => \App\Models\CommentTypes::inRandomOrder()->value('id') ?? \App\Models\CommentTypes::factory()->create()->id,
    'security_user_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
