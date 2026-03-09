<?php
declare(strict_types=1);

namespace App\Models\Examples;

use App\Models\Concerns\QueueableAlerts;
use Illuminate\Database\Eloquent\Model;

/**
 * POCOR-9509: Example Staff model demonstrating QueueableAlerts trait usage
 *
 * This example shows how different models can use the same trait with different default alert types.
 * Delete this file after reviewing - it's for documentation purposes only.
 *
 * @package App\Models\Examples
 */
class StaffExample extends Model
{
    use QueueableAlerts;

    protected $table = 'institution_staff';

    /**
     * POCOR-9509: Different default alert type for Staff model
     */
    protected $alertType = 'staff_alert';

    /**
     * Example: Notify staff member of upcoming appraisal
     */
    public function notifyAppraisalDue(string $staffEmail, \DateTimeInterface $dueDate): void
    {
        $message = "Your performance appraisal is due on {$dueDate->format('Y-m-d')}. Please prepare your self-assessment.";

        $this->queueEmail(
            $staffEmail,
            'Performance Appraisal Due',
            $message,
            'staff_appraisal',
            [
                'due_date' => $dueDate->format('Y-m-d'),
                'staff_id' => $this->id,
            ]
        );
    }

    /**
     * Example: Send urgent SMS to staff member
     */
    public function sendUrgentAlert(string $phoneNumber, string $urgentMessage): void
    {
        $this->queueSms(
            $phoneNumber,
            $urgentMessage,
            'staff_urgent',
            ['priority' => 'high']
        );
    }

    /**
     * Example: Batch send to multiple recipients
     */
    public function notifyAboutTraining(array $recipientEmails, array $trainingDetails): array
    {
        $results = [];

        foreach ($recipientEmails as $email) {
            $message = "Training: {$trainingDetails['title']} on {$trainingDetails['date']}. Location: {$trainingDetails['location']}.";

            $results[$email] = $this->queueEmail(
                $email,
                'Training Notification',
                $message,
                'staff_training',
                $trainingDetails
            );
        }

        return $results;
    }

    /**
     * Example: Queue alert with custom availability time
     */
    public function scheduleReminderEmail(string $email, \DateTimeInterface $remindAt): void
    {
        $this->queueAlert(
            'staff_reminder',
            'email',
            $email,
            'Reminder: Please complete your pending tasks.',
            'Task Reminder',
            ['reminder_type' => 'task_completion'],
            $remindAt // Alert becomes available for processing at this time
        );
    }
}
