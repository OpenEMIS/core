<?php

namespace Database\Factories\Api5;

use App\Models\Api5\ApiSecuritiesScopes;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ApiSecuritiesScopesFactory extends Factory
{
    protected $model = ApiSecuritiesScopes::class;

    public function definition(): array
    {
        $result = \DB::table('api_securities')
            ->leftJoin('api_securities_scopes', 'api_securities.id', '=', 'api_securities_scopes.api_security_id')
            ->whereNull('api_securities_scopes.api_security_id')
            ->select('api_securities.id as api_security_id')
            ->first();

        $api_security_id = $result->api_security_id ?? 1;
        $api_scope_id =  1;

        return [
            'api_security_id' => $api_security_id,
            'api_scope_id' => $api_scope_id,
            'index' => $this->faker->boolean(),
            'view' => $this->faker->boolean(),
            'add' => $this->faker->boolean(),
            'edit' => $this->faker->boolean(),
            'delete' => $this->faker->boolean(),
            'execute' => $this->faker->boolean(),
            'modified_user_id' => $this->faker->numberBetween(1, 2),
            'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
            'created_user_id' => $this->faker->numberBetween(1, 2),
            'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
        ];
    }
}
