<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;

class InstitutionStaffAttendances extends Model
{
    use HasFactory;
use InstitutionScope;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'staff_id', 'institution_id', 'academic_period_id', 'date', 'time_in', 'time_out', 'comment', 'modified_user_id', 'modified', 'created_user_id', 'created', 'absence_type_id', 'staff_id', 'institution_id', 'academic_period_id', 'modified_user_id', 'created_user_id', 'absence_type_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    protected $primaryKey = 'id';
    public $incrementing = false;
    public $timestamps = false;
    protected $table = "institution_staff_attendances";








private function emptyFunction() { return; }
}
