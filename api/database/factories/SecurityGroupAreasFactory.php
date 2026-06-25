<?php

namespace Database\Factories;

use App\Models\SecurityGroupAreas;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SecurityGroupAreasFactory extends Factory
{
    protected $model = SecurityGroupAreas::class;

    public function definition(): array
    {

        return [
    'security_group_id' =>  \App\Models\SecurityGroups::factory()->create()->id,
    'area_id' => \App\Models\Areas::inRandomOrder()->value('id') ?? \App\Models\Areas::factory()->create()->id,
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
