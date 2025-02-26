<?php

namespace Database\Factories;

use App\Models\UserSpecialNeedsReferrals;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class UserSpecialNeedsReferralsFactory extends Factory
{
    protected $model = UserSpecialNeedsReferrals::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'file_name' => $this->faker->lexify(str_repeat("?", 250)),
    'file_content' => $this->faker->word(),
    'comment' => $this->faker->text(50),
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? 1,
    'security_user_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? 1,
    'referrer_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? 1,
    'special_needs_referrer_type_id' => \App\Models\SpecialNeedsReferrerTypes::inRandomOrder()->value('id') ?? 1,
    'reason_type_id' => \App\Models\SpecialNeedTypes::inRandomOrder()->value('id') ?? 1,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
