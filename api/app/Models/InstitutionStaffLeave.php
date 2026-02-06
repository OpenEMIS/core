<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;

class InstitutionStaffLeave extends Model
{
    use HasFactory;
use InstitutionScope;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'date_from', 'date_to', 'start_time', 'end_time', 'full_day', 'comments', 'staff_id', 'staff_leave_type_id', 'institution_id', 'assignee_id', 'academic_period_id', 'status_id', 'number_of_days', 'file_name', 'file_content', 'modified_user_id', 'modified', 'created_user_id', 'created', 'staff_id', 'staff_leave_type_id', 'institution_id', 'assignee_id', 'academic_period_id', 'status_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    public $timestamps = false;
    protected $table = "institution_staff_leave";








    public function institution()
    {
        return $this->belongsTo(Institutions::class, 'institution_id', 'id');
    }


    public function staff()
    {
        return $this->belongsTo(SecurityUsers::class, 'staff_id', 'id');
    }


    public function assignee()
    {
        return $this->belongsTo(SecurityUsers::class, 'assignee_id', 'id');
    }

    public function securityUser()
    {
        return $this->belongsTo(SecurityUsers::class, 'created_user_id', 'id');
    }

    public function status()
    {
        return $this->belongsTo(WorkflowSteps::class, 'status_id', 'id');
    }


    public function staffLeaveType()
    {
        return $this->belongsTo(StaffLeaveType::class, 'staff_leave_type_id', 'id');
    }
}
