<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;

class InstitutionStaff extends Model
{
    use HasFactory;
use InstitutionScope;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'FTE', 'start_date', 'start_year', 'end_date', 'end_year', 'staff_id', 'staff_type_id', 'staff_status_id', 'institution_id', 'is_homeroom', 'institution_position_id', 'security_group_user_id', 'staff_position_grade_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'staff_id', 'staff_type_id', 'staff_status_id', 'institution_id', 'institution_position_id', 'security_group_user_id', 'staff_position_grade_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    public $timestamps = false;
    protected $table = "institution_staff";








    public function institution()
    {
        return $this->belongsTo(Institutions::class, 'institution_id', 'id');
    }

    public function staffStatus()
    {
        return $this->belongsTo(StaffStatuses::class, 'staff_status_id', 'id');
    }

    public function scopeWithStatus($query)
    {
        return $query->with('staffStatus:id,code,name');
    }

    public function staffType()
    {
        return $this->belongsTo(StaffTypes::class, 'staff_type_id', 'id');
    }


    public function institutionPosition()
    {
        return $this->belongsTo(InstitutionPositions::class, 'institution_position_id', 'id');
    }
    public function scopeWithPosition($query)
    {
        return $query->with([
            'institutionPosition:id,position_no,staff_position_title_id',
            'institutionPosition.staffPositionTitle:id,name'
        ]);
    }


    public function user()
    {
        return $this->belongsTo(SecurityUsers::class, 'staff_id', 'id');
    }
    public function scopeWithUser($query)
    {
        return $query->with('user:id,openemis_no,first_name,last_name');
    }


    public function classes()
    {
        return $this->hasMany(InstitutionClasses::class, 'staff_id', 'staff_id');
    }


    public function staffPositionGrade()
    {
        return $this->belongsTo(StaffPositionGrades::class, 'staff_position_grade_id', 'id');
    }


    //For POCOR-8491 Start...
    public function staffCustomFieldValue()
    {
        return $this->hasMany(StaffCustomFieldValues::class, 'staff_id', 'staff_id');
    }
    //For POCOR-8491 End...
}
