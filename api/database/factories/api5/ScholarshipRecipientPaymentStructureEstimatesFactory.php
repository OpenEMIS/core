<?php

namespace Database\Factories\Api5;

use App\Models\Api5\ScholarshipRecipientPaymentStructureEstimates;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ScholarshipRecipientPaymentStructureEstimatesFactory extends Factory
{
    protected $model = ScholarshipRecipientPaymentStructureEstimates::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'estimated_disbursement_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'estimated_amount' => $this->faker->randomFloat(2, 10, 1000),
    'comments' => $this->faker->text(50),
    'scholarship_disbursement_category_id' => \App\Models\ScholarshipDisbursementCategories::inRandomOrder()->value('id') ?? \App\Models\ScholarshipDisbursementCategories::factory()->create()->id,
    'scholarship_recipient_payment_structure_id' => \App\Models\ScholarshipRecipientPaymentStructures::inRandomOrder()->value('id') ?? \App\Models\ScholarshipRecipientPaymentStructures::factory()->create()->id,
    'recipient_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'scholarship_id' => \App\Models\Scholarships::inRandomOrder()->value('id') ?? \App\Models\Scholarships::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
