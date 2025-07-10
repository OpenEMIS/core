<?php

namespace Database\Factories;

use App\Models\InstitutionInfrastructureAttachments;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionInfrastructureAttachmentsFactory extends Factory
{
    protected $model = InstitutionInfrastructureAttachments::class;

    public function definition(): array
    {
        

        return [
    // Safe within INT
        'id' => $this->faker->numberBetween(1, 1000000),
    'name' => $this->faker->lexify(str_repeat("?", 50)),
    'description' => $this->faker->text(50),
    'file_name' => $this->faker->lexify(str_repeat("?", 250)),
    'file_content' => $this->faker->word(),
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? 1,
    'infrastructure_attachment_type_id' => \App\Models\InfrastructureAttachmentTypes::inRandomOrder()->value('id') ?? 1,
    'modified_user_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? 1,
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? 1,
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}