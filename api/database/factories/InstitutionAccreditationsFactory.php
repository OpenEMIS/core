<?php

namespace Database\Factories;

use App\Models\Api5\InstitutionAccreditations;
use Illuminate\Database\Eloquent\Factories\Factory;

//POCOR-9610: start - Factory for institution_accreditations (education_programme_id FK per spec)
class InstitutionAccreditationsFactory extends Factory
{
    protected $model = InstitutionAccreditations::class;

    public function definition(): array
    {
        //POCOR-9610: start - valid_from defaults to institution's date_opened (NOT NULL per spec)
        $institution = \App\Models\Institutions::inRandomOrder()->first();
        $institutionId = $institution?->id ?? \App\Models\Institutions::factory()->create()->id;
        $validFrom = $institution?->date_opened
            ? \Carbon\Carbon::parse($institution->date_opened)->format('Y-m-d')
            : \Carbon\Carbon::now()->format('Y-m-d');
        //POCOR-9610: end

        return [
            'id'                     => $this->model::max('id') + 1,
            'institution_id'         => $institutionId,
            'education_programme_id' => \App\Models\Api5\EducationProgrammes::inRandomOrder()->value('id') ?? 9,
            'valid_from'             => $validFrom,
            'valid_to'               => \Carbon\Carbon::now()->addYear()->format('Y-m-d'),
            'modified_user_id'       => 2,
            'modified'               => \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
            'created_user_id'        => 2,
            'created'                => \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
        ];
    }
}
//POCOR-9610: end
