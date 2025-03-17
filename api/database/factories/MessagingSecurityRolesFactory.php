<?php

namespace Database\Factories;

use App\Models\MessagingSecurityRoles;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class MessagingSecurityRolesFactory extends Factory
{
    protected $model = MessagingSecurityRoles::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'message_id' => \App\Models\Messaging::inRandomOrder()->value('id') ?? \App\Models\Messaging::factory()->create()->id,
    'security_role_id' => \App\Models\SecurityRoles::inRandomOrder()->value('id') ?? \App\Models\SecurityRoles::factory()->create()->id,
];
    }
}
