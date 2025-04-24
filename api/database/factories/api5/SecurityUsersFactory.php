<?php

namespace Database\Factories\Api5;

use App\Models\Api5\SecurityUsers;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SecurityUsersFactory extends Factory
{
    protected $model = SecurityUsers::class;

    public function definition(): array
    {
        return [
    'id' => $this->model::getNextId(),
    'username' => $this->faker->lexify(str_repeat("?", 100)),
    'password' => $this->faker->word(),
    'openemis_no' => $this->faker->lexify(str_repeat("?", 100)),
    'first_name' => $this->faker->lexify(str_repeat("?", 100)),
    'middle_name' => $this->faker->lexify(str_repeat("?", 100)),
    'third_name' => $this->faker->lexify(str_repeat("?", 100)),
    'last_name' => $this->faker->lexify(str_repeat("?", 100)),
    'preferred_name' => $this->faker->lexify(str_repeat("?", 100)),
    'email' => $this->faker->email(),
    'mobile_number' => $this->faker->phoneNumber(),
    'address' => $this->faker->text(50),
    'postal_code' => $this->faker->lexify(str_repeat("?", 20)),
    'address_area_id' => \App\Models\AreaAdministratives::inRandomOrder()->value('id') ?? \App\Models\AreaAdministratives::factory()->create()->id,
    'birthplace_area_id' =>  \App\Models\AreaAdministratives::inRandomOrder()->value('id') ?? \App\Models\AreaAdministratives::factory()->create()->id,
    'gender_id' => \App\Models\Genders::inRandomOrder()->value('id') ?? \App\Models\Genders::factory()->create()->id,
    'date_of_birth' => \Carbon\Carbon::now()->format("Y-m-d"),
    'date_of_death' => \Carbon\Carbon::now()->format("Y-m-d"),
    'nationality_id' =>  \App\Models\Nationalities::factory()->create()->id,
    'identity_type_id' =>  \App\Models\IdentityTypes::factory()->create()->id,
    'identity_number' => $this->faker->lexify(str_repeat("?", 50)),
    'external_reference' => $this->faker->lexify(str_repeat("?", 50)),
    'super_admin' => 0,
    'status' => $this->faker->numberBetween(0, 1),
    'last_login' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'failed_logins' => $this->faker->numberBetween(1, 1000),
    'photo_name' => $this->faker->lexify(str_repeat("?", 250)),
    'photo_content' => $this->faker->word(),
    'preferred_language' => 'en',
    'is_student' => $this->faker->numberBetween(0, 1),
    'is_staff' => $this->faker->numberBetween(0, 1),
    'is_guardian' => $this->faker->numberBetween(0, 1),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
