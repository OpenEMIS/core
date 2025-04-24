<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;
use App\Services\PermissionService;
use Illuminate\Database\Eloquent\Builder;
use Tymon\JWTAuth\Facades\JWTAuth;

class Institutions extends Model
{
    use HasFactory;
use InstitutionScope;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'name', 'alternative_name', 'code', 'address', 'postal_code', 'contact_person', 'telephone', 'fax', 'email', 'website', 'date_opened', 'year_opened', 'date_closed', 'year_closed', 'longitude', 'latitude', 'logo_name', 'logo_content', 'shift_type', 'classification', 'area_id', 'area_administrative_id', 'institution_locality_id', 'institution_type_id', 'institution_ownership_id', 'institution_status_id', 'institution_sector_id', 'institution_provider_id', 'institution_gender_id', 'security_group_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'area_id', 'area_administrative_id', 'institution_locality_id', 'institution_type_id', 'institution_ownership_id', 'institution_status_id', 'institution_sector_id', 'institution_provider_id', 'institution_gender_id', 'security_group_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    public $timestamps = false;
    protected $table = "institutions";

    protected $appends = ['code_name'];








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


    public function getCodeNameAttribute()
    {
        if(isset($this->attributes['code']) && isset($this->attributes['name'])) {
            return $this->attributes['code']. ' - ' .$this->attributes['name'];
        }
        if(isset($this->attributes['code']) && !isset($this->attributes['name'])) {
            return $this->attributes['code'];
        }
        if(isset($this->attributes['name']) && !isset($this->attributes['code'])) {
            return $this->attributes['name'];
        }
        return 'not selected';
    }

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('userInstitutionAccess', function (Builder $query) {
            $user = JWTAuth::user();

            if (!$user) {
                return;
            }

            // Check if user is a super admin (no restrictions)
            if ($user->super_admin ?? 0) {
                return;
            }

            $permissionService = app(PermissionService::class);
            $allowedInstitutions = $permissionService->getInstitutionIds();

            // Apply institution filter only if the user does not have access to all institutions
            if (!$permissionService->getAllowAllInstitutions()) {
                $query->whereIn('institutions.id', $allowedInstitutions);
            }
        });
    }

    //For POCOR-8252 Start...
    public function area()
    {
        return $this->belongsTo(Areas::class, 'area_id', 'id');
    }
    //For POCOR-8252 End...
}
