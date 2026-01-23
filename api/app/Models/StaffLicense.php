<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffLicense extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "staff_licenses";

    
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
        return $this->belongsTo(SecurityUsers::class, 'security_user_id', 'id');
    }


    public function status()
    {
        return $this->belongsTo(WorkflowSteps::class, 'status_id', 'id');
    }

    public function licenseType()
    {
        return $this->belongsTo(LicenseType::class, 'license_type_id', 'id');
    }
}
