<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;

class InstitutionPositions extends Model
{
    use HasFactory;
use InstitutionScope;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'status_id', 'position_no', 'staff_position_title_id', 'institution_id', 'assignee_id', 'shift_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'status_id', 'staff_position_title_id', 'institution_id', 'assignee_id', 'shift_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    public $timestamps = false;
    protected $table = "institution_positions";








    public function staffPositionTitle()
    {
        return $this->belongsTo(StaffPositionTitles::class, 'staff_position_title_id', 'id');
    }


    public function staffPositionGrades()
    {
        return $this->belongsTo(StaffPositionGrades::class, 'staff_position_grade_id', 'id');
    }

    public function status()
    {
        return $this->belongsTo(WorkflowSteps::class, 'status_id', 'id');
    }


    public function assignee()
    {
        return $this->belongsTo(SecurityUsers::class, 'assignee_id', 'id');
    }

    public function securityUser()
    {
        return $this->belongsTo(SecurityUsers::class, 'created_user_id', 'id');
    }


    public function institution()
    {
        return $this->belongsTo(Institutions::class, 'institution_id', 'id');
    }


    public function institutionStaff()
    {
        return $this->belongsTo(InstitutionStaff::class, 'assignee_id', 'staff_id');
    }
}
