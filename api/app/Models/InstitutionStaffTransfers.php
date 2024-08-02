<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionStaffTransfers extends Model
{
    use HasFactory;

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
