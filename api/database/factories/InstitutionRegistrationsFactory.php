<?php

namespace Database\Factories;

use App\Models\Api5\InstitutionRegistrations;
use Illuminate\Database\Eloquent\Factories\Factory;

class InstitutionRegistrationsFactory extends Factory
{
    protected $model = InstitutionRegistrations::class;

    public function definition(): array
    {
        return [
            'id' => $this->model::max('id') + 1,
            'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
            'external_id' => 'ACC-INS-' . str_pad((string) $this->faker->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'status' => $this->faker->randomElement(['pending', 'active', 'revoked', 'expired']),
            'approved_date' => \Carbon\Carbon::now()->subDays(5)->format('Y-m-d'),
            'valid_from' => \Carbon\Carbon::now()->format('Y-m-d'),
            'valid_to' => \Carbon\Carbon::now()->addYears(3)->format('Y-m-d'),
            'decision_reference' => 'DEC-' . $this->faker->unique()->numerify('######'),
            'modified_user_id' => 2,
            'modified' => \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
            'created_user_id' => 2,
            'created' => \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
        ];
    }
}
