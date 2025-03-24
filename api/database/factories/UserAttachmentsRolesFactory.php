<?php

namespace Database\Factories;

use App\Models\UserAttachmentsRoles;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class UserAttachmentsRolesFactory extends Factory
{
    protected $model = UserAttachmentsRoles::class;

    public function definition(): array
    {

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'user_attachment_id' => \App\Models\UserAttachments::inRandomOrder()->value('id') ?? \App\Models\UserAttachments::factory()->create()->id,
    'security_role_id' => \App\Models\SecurityRoles::inRandomOrder()->value('id') ?? \App\Models\SecurityRoles::factory()->create()->id,
];
    }
}
