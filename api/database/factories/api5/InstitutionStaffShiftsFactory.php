<?php

namespace Database\Factories\Api5;

use App\Models\Api5\InstitutionStaffShifts;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionStaffShiftsFactory extends Factory
{
    protected $model = InstitutionStaffShifts::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'staff_id' => \App\Models\SecurityUsers::inRandomOrder()->value('id') ?? \App\Models\SecurityUsers::factory()->create()->id,
    'shift_id' => \App\Models\InstitutionShifts::inRandomOrder()->value('id') ?? \App\Models\InstitutionShifts::factory()->create()->id,
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
