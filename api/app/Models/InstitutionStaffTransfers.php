<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionStaffTransfers extends Model
{
    use HasFactory;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'staff_id', 'new_institution_id', 'previous_institution_id', 'status_id', 'assignee_id', 'new_institution_position_id', 'new_staff_type_id', 'new_FTE', 'new_start_date', 'new_end_date', 'previous_institution_staff_id', 'previous_staff_type_id', 'previous_FTE', 'previous_end_date', 'previous_effective_date', 'comment', 'transfer_type', 'all_visible', 'is_homeroom', 'modified_user_id', 'modified', 'created_user_id', 'created', 'staff_id', 'new_institution_id', 'previous_institution_id', 'status_id', 'assignee_id', 'new_institution_position_id', 'new_staff_type_id', 'previous_institution_staff_id', 'previous_staff_type_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    public $timestamps = false;
    protected $table = "institution_staff_transfers";








    public function newInstitution()
    {
        return $this->belongsTo(Institutions::class, 'new_institution_id', 'id');
    }

    public function previousInstitution()
    {
        return $this->belongsTo(Institutions::class, 'previous_institution_id', 'id');
    }

    public function assignee()
    {
        return $this->belongsTo(SecurityUsers::class, 'assignee_id', 'id');
    }

    public function securityUser()
    {
        return $this->belongsTo(SecurityUsers::class, 'created_user_id', 'id');
    }


    public function user()
    {
        return $this->belongsTo(SecurityUsers::class, 'staff_id', 'id');
    }


    public function status()
    {
        return $this->belongsTo(WorkflowSteps::class, 'status_id', 'id');
    }
}
