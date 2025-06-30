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


        return [
    'api_credential_id' =>  \App\Models\ApiCredentials::factory()->create()->id,
    'api_scope_id' =>  \App\Models\ApiScopes::factory()->create()->id,
];
    }
}
