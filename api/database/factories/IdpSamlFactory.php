<?php

namespace Database\Factories;

use App\Models\IdpSaml;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class IdpSamlFactory extends Factory
{
    protected $model = IdpSaml::class;

    public function definition(): array
    {


        return [
    'system_authentication_id' =>  \App\Models\SystemAuthentications::factory()->create()->id,
    'idp_entity_id' => $this->faker->lexify(str_repeat("?", 200)),
    'idp_sso' => $this->faker->lexify(str_repeat("?", 200)),
    'idp_sso_binding' => $this->faker->lexify(str_repeat("?", 100)),
    'idp_slo' => $this->faker->lexify(str_repeat("?", 200)),
    'idp_slo_binding' => $this->faker->lexify(str_repeat("?", 100)),
    'idp_x509cert' => $this->faker->text(50),
    'idp_cert_fingerprint' => $this->faker->lexify(str_repeat("?", 100)),
    'idp_cert_fingerprint_algorithm' => $this->faker->lexify(str_repeat("?", 10)),
    'sp_entity_id' => $this->faker->lexify(str_repeat("?", 200)),
    'sp_acs' => $this->faker->lexify(str_repeat("?", 200)),
    'sp_slo' => $this->faker->lexify(str_repeat("?", 100)),
    'sp_name_id_format' => $this->faker->lexify(str_repeat("?", 100)),
    'sp_private_key' => $this->faker->text(50),
    'sp_metadata' => $this->faker->text(50),
];
    }
}
