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
        $attempts = 0;
do {
    $security_group_id = $this->faker->randomElement(\App\Models\SecurityGroupAreas::pluck('security_group_id')->toArray()) ?? 1;
            $area_id = $this->faker->randomElement(\App\Models\SecurityGroupAreas::pluck('area_id')->toArray()) ?? 1;
    $exists = SecurityGroupAreas::where('security_group_id', $security_group_id)
                ->where('area_id', $area_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'security_group_id' => \App\Models\SecurityGroups::inRandomOrder()->value('id') ?? \App\Models\SecurityGroups::factory()->create()->id,
    'area_id' => \App\Models\Areas::inRandomOrder()->value('id') ?? \App\Models\Areas::factory()->create()->id,
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
