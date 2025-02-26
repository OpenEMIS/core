<?php

namespace Database\Factories;

use App\Models\UserNationalities;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class UserNationalitiesFactory extends Factory
{
    protected $model = UserNationalities::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $nationality_id = $this->faker->randomElement(\App\Models\UserNationalities::pluck('nationality_id')->toArray()) ?? 1;
            $security_user_id = $this->faker->randomElement(\App\Models\UserNationalities::pluck('security_user_id')->toArray()) ?? 1;
    $exists = UserNationalities::where('nationality_id', $nationality_id)
                ->where('security_user_id', $security_user_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'comments' => $this->faker->text(50),
    'preferred' => $this->faker->numberBetween(1, 1000),
    'nationality_id' => \App\Models\Nationalities::inRandomOrder()->value('id') ?? \App\Models\Nationalities::factory()->create()->id,
    'security_user_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
