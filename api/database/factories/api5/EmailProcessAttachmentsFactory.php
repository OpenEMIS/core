<?php

namespace Database\Factories\Api5;

use App\Models\Api5\EmailProcessAttachments;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class EmailProcessAttachmentsFactory extends Factory
{
    protected $model = EmailProcessAttachments::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'file_name' => $this->faker->lexify(str_repeat("?", 250)),
    'file_content' => $this->faker->word(),
    'email_process_id' => \App\Models\EmailProcesses::inRandomOrder()->value('id') ?? \App\Models\EmailProcesses::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
