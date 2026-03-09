<?php
declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\QueueableAlerts;
use App\Services\AlertProcessor\PlaceholderReplacer;
use App\Services\AlertProcessor\RecipientResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * POCOR-9509: Student Attendance Marked Record Model
 *
 * This model demonstrates how to integrate the Laravel alert system
 * into an API model that tracks student attendance.
 *
 * Features:
 * - Uses QueueableAlerts trait for basic queueing
 * - Uses PlaceholderReplacer for template processing
 * - Uses RecipientResolver for finding alert recipients
 * - Checks threshold before triggering alerts
 * - Checks user roles to determine who gets alerts
 * - Prepares and sends alerts when absence threshold is reached
 *
 * @package App\Models
 */
class StudentAttendanceMarkedRecord extends Model
{
    use QueueableAlerts;

    protected $table = 'student_absences_period_details';



    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'student_id',
        'institution_id',
        'academic_period_id',
        'institution_class_id',
        'period',
        'subject_id',
        'date',
        'time_in',
        'time_out',
        'absence_type_id',
        'comment',
    ];


}
