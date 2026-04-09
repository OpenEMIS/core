<?php

namespace Database\Factories;

use App\Models\Api5\InstitutionAccreditations;
use Illuminate\Database\Eloquent\Factories\Factory;

class InstitutionAccreditationsFactory extends Factory
{
    protected $model = InstitutionAccreditations::class;

    public function definition(): array
    {
        return [
            'id' => $this->model::max('id') + 1,
            'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
            'programme_name' => 'Programme ' . $this->faker->unique()->word(),
            'programme_code' => strtoupper($this->faker->unique()->bothify('PRG-###')),
            'qualification_level' => $this->faker->randomElement(['Certificate', 'Diploma', 'Degree']),
            'status' => $this->faker->randomElement(['pending', 'active', 'revoked', 'expired']),
            'valid_from' => \Carbon\Carbon::now()->format('Y-m-d'),
            'valid_to' => \Carbon\Carbon::now()->addYears(3)->format('Y-m-d'),
            'external_id' => 'ACC-PROG-' . str_pad((string) $this->faker->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'modified_user_id' => 2,
            'modified' => \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
            'created_user_id' => 2,
            'created' => \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
        ];
    }
}
