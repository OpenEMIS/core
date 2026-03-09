<?php
declare(strict_types=1);

namespace App\Models\Examples;

use App\Models\Concerns\QueueableAlerts;
use Illuminate\Database\Eloquent\Model;

/**
 * POCOR-9509: Example Student model demonstrating QueueableAlerts trait usage
 *
 * This is an example showing how to add alert queueing capabilities to any model.
 * Delete this file after reviewing - it's for documentation purposes only.
 *
 * @package App\Models\Examples
 */
class StudentExample extends Model
{
    use QueueableAlerts;

    protected $table = 'institution_students';

    /**
     * POCOR-9509: Default alert type for this model
     * All alerts queued via this model will use this type unless overridden
     */
    protected $alertType = 'student_alert';

    /**
     * Example: Send attendance warning to student's guardian
     */
    public function sendAttendanceWarning(string $guardianEmail, int $absentDays): void
    {
        $message = "Your child has been absent for {$absentDays} days. Please contact the school.";

        // Queue email - uses model's default alertType ('student_alert')
        $this->queueEmail(
            $guardianEmail,
            'Attendance Warning',
            $message,
            'student_attendance', // Override default alert type
            [
                'absent_days' => $absentDays,
                'student_name' => $this->full_name ?? 'Student',
            ]
        );
    }

    /**
     * Example: Send SMS notification to parent
     */
    public function sendParentSmsNotification(string $phoneNumber, string $message): void
    {
        $this->queueSms(
            $phoneNumber,
            $message,
            'parent_notification',
            ['notification_type' => 'parent_sms']
        );
    }

    /**
     * Example: Send delayed welcome email (scheduled for future)
     */
    public function sendDelayedWelcomeEmail(string $email, \DateTimeInterface $enrollmentDate): void
    {
        // Send welcome email on enrollment date
        $this->queueDelayedAlert(
            'email',
            $email,
            'Welcome to our school! Your enrollment is confirmed.',
            $enrollmentDate,
            'Welcome to School',
            'student_enrollment',
            ['enrollment_date' => $enrollmentDate->format('Y-m-d')]
        );
    }

    /**
     * Example: Check alert queue status for this student
     */
    public function checkAlertStatus(): array
    {
        return [
            'queue_stats' => $this->getAlertQueueStats(),
            'pending_alerts' => $this->getQueuedAlerts(0), // Get pending alerts
            'failed_alerts' => $this->getQueuedAlerts(-1), // Get failed alerts
        ];
    }
}
