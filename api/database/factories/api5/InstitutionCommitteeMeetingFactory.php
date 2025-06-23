<?php

namespace Database\Factories\Api5;

use App\Models\Api5\InstitutionCommitteeMeeting;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionCommitteeMeetingFactory extends Factory
{
    protected $model = InstitutionCommitteeMeeting::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'meeting_date' => $this->faker->lexify(str_repeat("?", 250)),
    'start_time' => $this->faker->lexify(str_repeat("?", 255)),
    'end_time' => $this->faker->lexify(str_repeat("?", 255)),
    'comment' => $this->faker->lexify(str_repeat("?", 255)),
    'institution_committee_id' => \App\Models\InstitutionCommittees::inRandomOrder()->value('id') ?? \App\Models\InstitutionCommittees::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
