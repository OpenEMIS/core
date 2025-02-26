<?php

namespace Database\Factories;

use App\Models\SecurityGroupInstitutions;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SecurityGroupInstitutionsFactory extends Factory
{
    protected $model = SecurityGroupInstitutions::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $security_group_id = $this->faker->randomElement(\App\Models\SecurityGroupInstitutions::pluck('security_group_id')->toArray()) ?? 1;
            $institution_id = $this->faker->randomElement(\App\Models\SecurityGroupInstitutions::pluck('institution_id')->toArray()) ?? 1;
    $exists = SecurityGroupInstitutions::where('security_group_id', $security_group_id)
                ->where('institution_id', $institution_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'security_group_id' => \App\Models\SecurityGroups::inRandomOrder()->value('id') ?? 1,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? 1,
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
