<?php

namespace Database\Factories\Api5;

use App\Models\Api5\SystemPatches;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use function Symfony\Component\String\s;

class SystemPatchesFactory extends Factory
{
    protected $model = SystemPatches::class;

    public function definition(): array
    {
        $faker  = $this->faker;

        return [
    'issue' => (string) $faker->unique()->regexify('[A-Za-z0-9]{15}'),
    'version' => $this->faker->lexify(str_repeat("?", 15)),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
