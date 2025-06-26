<?php

namespace Database\Factories;

use App\Models\InstitutionAttachments;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionAttachmentsFactory extends Factory
{
    protected $model = InstitutionAttachments::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'institution_attachment_type_id' => \App\Models\InstitutionAttachmentTypes::inRandomOrder()->value('id') ?? \App\Models\InstitutionAttachmentTypes::factory()->create()->id,
    'name' => $this->faker->lexify(str_repeat("?", 250)),
    'description' => $this->faker->text(50),
    'file_name' => $this->faker->lexify(str_repeat("?", 250)),
    'file_content' => $this->faker->word(),
    'date_on_file' => \Carbon\Carbon::now()->format("Y-m-d"),
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
