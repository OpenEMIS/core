<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionStaff extends Model
{
    use HasFactory;

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

    public function staffType()
    {
        return $this->belongsTo(StaffTypes::class, 'staff_type_id', 'id');
    }


    public function institutionPosition()
    {
        return $this->belongsTo(InstitutionPositions::class, 'institution_position_id', 'id');
    }


    public function user()
    {
        return $this->belongsTo(SecurityUsers::class, 'staff_id', 'id');
    }
}
