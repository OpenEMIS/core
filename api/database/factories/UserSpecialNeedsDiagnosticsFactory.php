<?php

namespace Database\Factories;

use App\Models\UserSpecialNeedsDiagnostics;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class UserSpecialNeedsDiagnosticsFactory extends Factory
{
    protected $model = UserSpecialNeedsDiagnostics::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'file_name' => $this->faker->lexify(str_repeat("?", 250)),
    'file_content' => $this->faker->word(),
    'comment' => $this->faker->text(50),
    'special_needs_diagnostics_type_id' => $this->faker->numberBetween(1, 1000),
    'special_needs_diagnostics_degree_id' => \App\Models\SpecialNeedsDiagnosticsDegree::inRandomOrder()->value('id') ?? \App\Models\SpecialNeedsDiagnosticsDegree::factory()->create()->id,
    'security_user_id' => $this->faker->numberBetween(1, 1000),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
