<?php

namespace Database\Factories;

use App\Models\ApiCredentialsScopes;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ApiCredentialsScopesFactory extends Factory
{
    protected $model = ApiCredentialsScopes::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $api_credential_id = $this->faker->randomElement(\App\Models\ApiCredentialsScopes::pluck('api_credential_id')->toArray()) ?? 1;
            $api_scope_id = $this->faker->randomElement(\App\Models\ApiCredentialsScopes::pluck('api_scope_id')->toArray()) ?? 1;
    $exists = ApiCredentialsScopes::where('api_credential_id', $api_credential_id)
                ->where('api_scope_id', $api_scope_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'api_credential_id' => \App\Models\ApiCredentials::inRandomOrder()->value('id') ?? \App\Models\ApiCredentials::factory()->create()->id,
    'api_scope_id' => \App\Models\ApiScopes::inRandomOrder()->value('id') ?? \App\Models\ApiScopes::factory()->create()->id,
];
    }
}
