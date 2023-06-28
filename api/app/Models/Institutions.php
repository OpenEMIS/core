<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Institutions extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "institutions";

    public function areaAdministratives()
    {
        return $this->belongsTo(AreaAdministratives::class, 'area_administrative_id', 'id');
    }


    public function areaEducation()
    {
        return $this->belongsTo(Areas::class, 'area_id', 'id');
    }


    public function educationGrades()
    {
        return $this->belongsToMany(EducationGrades::class, 'institution_grades', 'institution_id', 'education_grade_id');
    }


    public function institutionLocalities()
    {
        return $this->belongsTo(InstitutionLocalities::class, 'institution_locality_id', 'id');
    }


    public function institutionOwnerships()
    {
        return $this->belongsTo(InstitutionOwnerships::class, 'institution_ownership_id', 'id');
    }


    public function institutionProviders()
    {
        return $this->belongsTo(InstitutionProviders::class, 'institution_provider_id', 'id');
    }


    public function institutionSectors()
    {
        return $this->belongsTo(InstitutionSectors::class, 'institution_sector_id', 'id');
    }


    public function institutionTypes()
    {
        return $this->belongsTo(InstitutionTypes::class, 'institution_type_id', 'id');
    }


    public function institutionStatus()
    {
        return $this->belongsTo(InstitutionStatus::class, 'institution_status_id', 'id');
    }


    public function institutionGender()
    {
        return $this->belongsTo(InstitutionGender::class, 'institution_gender_id', 'id');
    }
}
