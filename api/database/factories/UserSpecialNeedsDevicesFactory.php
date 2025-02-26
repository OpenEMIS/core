<?php

namespace Database\Factories;

use App\Models\UserSpecialNeedsDevices;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class UserSpecialNeedsDevicesFactory extends Factory
{
    protected $model = UserSpecialNeedsDevices::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'comment' => $this->faker->text(50),
    'security_user_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'special_needs_device_type_id' => \App\Models\SpecialNeedsDeviceTypes::inRandomOrder()->value('id') ?? \App\Models\SpecialNeedsDeviceTypes::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
