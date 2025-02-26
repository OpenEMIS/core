<?php

namespace Database\Factories;

use App\Models\InstitutionAssociationStaff;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionAssociationStaffFactory extends Factory
{
    protected $model = InstitutionAssociationStaff::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'security_user_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'institution_association_id' => \App\Models\InstitutionAssociations::inRandomOrder()->value('id') ?? \App\Models\InstitutionAssociations::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
