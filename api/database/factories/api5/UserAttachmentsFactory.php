<?php

namespace Database\Factories\Api5;

use App\Models\Api5\UserAttachments;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class UserAttachmentsFactory extends Factory
{
    protected $model = UserAttachments::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'student_attachment_type_id' => \App\Models\StudentAttachmentTypes::inRandomOrder()->value('id') ?? \App\Models\StudentAttachmentTypes::factory()->create()->id,
    'staff_attachment_type_id' => \App\Models\StaffAttachmentTypes::inRandomOrder()->value('id') ?? \App\Models\StaffAttachmentTypes::factory()->create()->id,
    'name' => $this->faker->lexify(str_repeat("?", 250)),
    'description' => $this->faker->text(50),
    'file_name' => $this->faker->lexify(str_repeat("?", 250)),
    'file_content' => $this->faker->word(),
    'date_on_file' => \Carbon\Carbon::now()->format("Y-m-d"),
    'security_user_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
