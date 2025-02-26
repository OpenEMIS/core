<?php

namespace Database\Factories;

use App\Models\ScholarshipRecipientCollections;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ScholarshipRecipientCollectionsFactory extends Factory
{
    protected $model = ScholarshipRecipientCollections::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'payment_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'amount' => $this->faker->randomFloat(2, 10, 1000),
    'comments' => $this->faker->text(50),
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? 1,
    'recipient_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? 1,
    'scholarship_id' => \App\Models\Scholarships::inRandomOrder()->value('id') ?? 1,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
