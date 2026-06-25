<?php
//POCOR-8630 start
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Archived staff attendance rows (institution_staff_attendances_archived).
 */
class InstitutionStaffAttendancesArchived extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'institution_staff_attendances_archived';
}
//POCOR-8630 end
