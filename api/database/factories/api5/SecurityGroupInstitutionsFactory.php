<?php

namespace Database\Factories\Api5;

use App\Models\Api5\SecurityGroupInstitutions;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SecurityGroupInstitutionsFactory extends Factory
{
    protected $model = SecurityGroupInstitutions::class;

    public function definition(): array
    {

        return [
    'security_group_id' => \App\Models\SecurityGroups::factory()->create()->id,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
