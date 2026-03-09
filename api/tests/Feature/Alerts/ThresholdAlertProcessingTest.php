<?php
/**
 * POCOR-9509: Threshold Alert Processing Tests
 *
 * Tests for the ThresholdAlertTrait used by models like
 * InstitutionStudentAbsenceDetails and InstitutionStaffAbsenceDetails
 */

namespace Tests\Feature\Alerts;

use App\Models\Api5\InstitutionStudentAbsenceDetails;
use App\Models\Api5\SystemProcesses;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ThresholdAlertProcessingTest extends TestCase
{
    use RefreshDatabase;

    protected $institutionId;
    protected $studentId;
    protected $academicPeriodId;
    protected $institutionClassId;

    public function setUp(): void
    {
        parent::setUp();

        // Setup test data
        $this->institutionId = 1;
        $this->studentId = 100;
        $this->academicPeriodId = 1;
        $this->institutionClassId = 1;

        // Create alert rule if not exists
        DB::table('alerts')->updateOrInsert(
            ['name' => 'StudentAttendance'],
            ['frequency' => 'Daily', 'created' => now(), 'modified' => now()]
        );

        DB::table('alert_rules')->updateOrInsert(
            ['feature' => 'StudentAttendance'],
            [
                'subject' => 'Student Absence Alert: ${student.name}',
                'message' => 'Student ${student.name} has reached ${current_value} absences (threshold: ${threshold})',
                'method' => 'email,sms',
                'threshold' => 3,
                'enabled' => 1,
                'created' => now(),
                'modified' => now(),
            ]
        );

        // Create test role
        DB::table('security_roles')->updateOrInsert(
            ['id' => 1],
            ['name' => 'Parent', 'created' => now(), 'modified' => now()]
        );

        // Assign role to alert rule
        DB::table('alerts_roles')->updateOrInsert(
            ['alert_rule_id' => 1, 'security_role_id' => 1],
            ['created' => now(), 'modified' => now()]
        );
    }

    /** @test */
    public function it_creates_system_processes_record_with_status_0_when_alert_processing_starts()
    {
        // Create test absence record
        $absence = InstitutionStudentAbsenceDetails::create([
            'student_id' => $this->studentId,
            'institution_id' => $this->institutionId,
            'academic_period_id' => $this->academicPeriodId,
            'institution_class_id' => $this->institutionClassId,
            'date' => now()->toDateString(),
            'period' => 1,
            'subject_id' => 1,
            'absence_type_id' => 1,
        ]);

        // Verify SystemProcesses record exists
        $process = DB::table('system_processes')
            ->where('model', InstitutionStudentAbsenceDetails::class)
            ->where('callable_event', 'StudentAttendance.markAbsence')
            ->first();

        $this->assertNotNull($process);
        $this->assertEquals(0, $process->status);
        $this->assertEquals('Student Attendance Alert Processing', $process->name);
    }

    /** @test */
    public function it_queues_alerts_when_threshold_is_reached()
    {
        // Create parent-student relationship
        DB::table('security_users')->updateOrInsert(
            ['id' => $this->studentId],
            ['email' => 'student@example.com', 'first_name' => 'John', 'last_name' => 'Doe']
        );

        // Create parent user
        $parentId = 200;
        DB::table('security_users')->updateOrInsert(
            ['id' => $parentId],
            ['email' => 'parent@example.com', 'mobile_number' => '1234567890']
        );

        // Create 3 absence records to reach threshold of 3
        for ($i = 0; $i < 3; $i++) {
            InstitutionStudentAbsenceDetails::create([
                'student_id' => $this->studentId,
                'institution_id' => $this->institutionId,
                'academic_period_id' => $this->academicPeriodId,
                'institution_class_id' => $this->institutionClassId,
                'date' => now()->subDays($i)->toDateString(),
                'period' => 1,
                'subject_id' => 1,
                'absence_type_id' => 1,
            ]);
        }

        // Verify alerts were queued
        $alertCount = DB::table('alerts_queue')
            ->where('alert_type', 'StudentAttendance')
            ->count();

        $this->assertGreaterThan(0, $alertCount);
    }

    /** @test */
    public function it_does_not_queue_alerts_when_threshold_is_not_reached()
    {
        // Create only 1 absence (threshold is 3)
        InstitutionStudentAbsenceDetails::create([
            'student_id' => $this->studentId,
            'institution_id' => $this->institutionId,
            'academic_period_id' => $this->academicPeriodId,
            'institution_class_id' => $this->institutionClassId,
            'date' => now()->toDateString(),
            'period' => 1,
            'subject_id' => 1,
            'absence_type_id' => 1,
        ]);

        // Verify no alerts were queued
        $alertCount = DB::table('alerts_queue')
            ->where('alert_type', 'StudentAttendance')
            ->count();

        $this->assertEquals(0, $alertCount);
    }

    /** @test */
    public function it_queues_email_alerts_when_email_method_is_enabled()
    {
        // Update alert rule to only email
        DB::table('alert_rules')
            ->where('feature', 'StudentAttendance')
            ->update(['method' => 'email']);

        // Create parent with email
        $parentId = 200;
        DB::table('security_users')->updateOrInsert(
            ['id' => $parentId],
            ['email' => 'parent@example.com']
        );

        // Create student
        DB::table('security_users')->updateOrInsert(
            ['id' => $this->studentId],
            ['first_name' => 'John', 'last_name' => 'Doe', 'email' => 'student@example.com']
        );

        // Create 3 absences to trigger alert
        for ($i = 0; $i < 3; $i++) {
            InstitutionStudentAbsenceDetails::create([
                'student_id' => $this->studentId,
                'institution_id' => $this->institutionId,
                'academic_period_id' => $this->academicPeriodId,
                'institution_class_id' => $this->institutionClassId,
                'date' => now()->subDays($i)->toDateString(),
                'period' => 1,
                'subject_id' => 1,
                'absence_type_id' => 1,
            ]);
        }

        // Verify email alert was queued
        $emailAlert = DB::table('alerts_queue')
            ->where('channel', 'email')
            ->where('alert_type', 'StudentAttendance')
            ->first();

        $this->assertNotNull($emailAlert);
        $this->assertEquals(0, $emailAlert->status); // pending
    }

    /** @test */
    public function it_queues_sms_alerts_when_sms_method_is_enabled()
    {
        // Update alert rule to only SMS
        DB::table('alert_rules')
            ->where('feature', 'StudentAttendance')
            ->update(['method' => 'sms']);

        // Create parent with phone
        $parentId = 200;
        DB::table('security_users')->updateOrInsert(
            ['id' => $parentId],
            ['mobile_number' => '1234567890']
        );

        // Create student
        DB::table('security_users')->updateOrInsert(
            ['id' => $this->studentId],
            ['first_name' => 'John', 'last_name' => 'Doe']
        );

        // Create 3 absences to trigger alert
        for ($i = 0; $i < 3; $i++) {
            InstitutionStudentAbsenceDetails::create([
                'student_id' => $this->studentId,
                'institution_id' => $this->institutionId,
                'academic_period_id' => $this->academicPeriodId,
                'institution_class_id' => $this->institutionClassId,
                'date' => now()->subDays($i)->toDateString(),
                'period' => 1,
                'subject_id' => 1,
                'absence_type_id' => 1,
            ]);
        }

        // Verify SMS alert was queued
        $smsAlert = DB::table('alerts_queue')
            ->where('channel', 'sms')
            ->where('alert_type', 'StudentAttendance')
            ->first();

        $this->assertNotNull($smsAlert);
        $this->assertEquals(0, $smsAlert->status); // pending
    }

    /** @test */
    public function it_includes_alert_metadata_in_payload()
    {
        // Create parent
        $parentId = 200;
        DB::table('security_users')->updateOrInsert(
            ['id' => $parentId],
            ['email' => 'parent@example.com']
        );

        // Create student
        DB::table('security_users')->updateOrInsert(
            ['id' => $this->studentId],
            ['first_name' => 'John', 'last_name' => 'Doe']
        );

        // Create 3 absences
        for ($i = 0; $i < 3; $i++) {
            InstitutionStudentAbsenceDetails::create([
                'student_id' => $this->studentId,
                'institution_id' => $this->institutionId,
                'academic_period_id' => $this->academicPeriodId,
                'institution_class_id' => $this->institutionClassId,
                'date' => now()->subDays($i)->toDateString(),
                'period' => 1,
                'subject_id' => 1,
                'absence_type_id' => 1,
            ]);
        }

        // Verify alert payload contains context
        $alert = DB::table('alerts_queue')
            ->where('alert_type', 'StudentAttendance')
            ->first();

        $this->assertNotNull($alert);

        $payload = json_decode($alert->payload, true);
        $this->assertArrayHasKey('student_id', $payload);
        $this->assertEquals($this->studentId, $payload['student_id']);
        $this->assertArrayHasKey('institution_id', $payload);
        $this->assertArrayHasKey('academic_period_id', $payload);
    }

    /** @test */
    public function it_replaces_placeholders_in_alert_message()
    {
        // Create parent
        $parentId = 200;
        DB::table('security_users')->updateOrInsert(
            ['id' => $parentId],
            ['email' => 'parent@example.com']
        );

        // Create student
        DB::table('security_users')->updateOrInsert(
            ['id' => $this->studentId],
            ['first_name' => 'John', 'last_name' => 'Doe', 'openemis_no' => 'STU001']
        );

        // Create 3 absences
        for ($i = 0; $i < 3; $i++) {
            InstitutionStudentAbsenceDetails::create([
                'student_id' => $this->studentId,
                'institution_id' => $this->institutionId,
                'academic_period_id' => $this->academicPeriodId,
                'institution_class_id' => $this->institutionClassId,
                'date' => now()->subDays($i)->toDateString(),
                'period' => 1,
                'subject_id' => 1,
                'absence_type_id' => 1,
            ]);
        }

        // Verify placeholders were replaced
        $alert = DB::table('alerts_queue')
            ->where('alert_type', 'StudentAttendance')
            ->first();

        $this->assertNotNull($alert);
        $this->assertStringNotContainsString('${student.name}', $alert->message_body);
        $this->assertStringNotContainsString('${current_value}', $alert->message_body);
        $this->assertStringContainsString('John Doe', $alert->message_body);
        $this->assertStringContainsString('3', $alert->message_body); // current value
    }

    /** @test */
    public function it_handles_missing_alert_rule_gracefully()
    {
        // Remove alert rule
        DB::table('alert_rules')->where('feature', 'StudentAttendance')->delete();

        // Create absence record
        $absence = InstitutionStudentAbsenceDetails::create([
            'student_id' => $this->studentId,
            'institution_id' => $this->institutionId,
            'academic_period_id' => $this->academicPeriodId,
            'institution_class_id' => $this->institutionClassId,
            'date' => now()->toDateString(),
            'period' => 1,
            'subject_id' => 1,
            'absence_type_id' => 1,
        ]);

        // Verify no alerts queued
        $alertCount = DB::table('alerts_queue')->count();
        $this->assertEquals(0, $alertCount);
    }

    /** @test */
    public function it_only_processes_alerts_for_newly_created_records()
    {
        // Create initial record
        $absence = InstitutionStudentAbsenceDetails::create([
            'student_id' => $this->studentId,
            'institution_id' => $this->institutionId,
            'academic_period_id' => $this->academicPeriodId,
            'institution_class_id' => $this->institutionClassId,
            'date' => now()->toDateString(),
            'period' => 1,
            'subject_id' => 1,
            'absence_type_id' => 1,
        ]);

        $processCountBefore = DB::table('system_processes')->count();

        // Update the record (should not trigger alert processing)
        $absence->update(['comment' => 'Updated']);

        $processCountAfter = DB::table('system_processes')->count();

        // Count should not increase on update
        $this->assertEquals($processCountBefore, $processCountAfter);
    }

    /** @test */
    public function it_has_alert_queue_records_with_correct_initial_status()
    {
        // Create parent
        $parentId = 200;
        DB::table('security_users')->updateOrInsert(
            ['id' => $parentId],
            ['email' => 'parent@example.com']
        );

        // Create student
        DB::table('security_users')->updateOrInsert(
            ['id' => $this->studentId],
            ['first_name' => 'John', 'last_name' => 'Doe']
        );

        // Create 3 absences
        for ($i = 0; $i < 3; $i++) {
            InstitutionStudentAbsenceDetails::create([
                'student_id' => $this->studentId,
                'institution_id' => $this->institutionId,
                'academic_period_id' => $this->academicPeriodId,
                'institution_class_id' => $this->institutionClassId,
                'date' => now()->subDays($i)->toDateString(),
                'period' => 1,
                'subject_id' => 1,
                'absence_type_id' => 1,
            ]);
        }

        // Verify all alerts have status 0 (pending)
        $alerts = DB::table('alerts_queue')
            ->where('alert_type', 'StudentAttendance')
            ->get();

        $this->assertTrue($alerts->count() > 0);
        foreach ($alerts as $alert) {
            $this->assertEquals(0, $alert->status);
        }
    }

    /** @test */
    public function it_includes_retry_count_field_in_alert_queue()
    {
        // Create parent
        $parentId = 200;
        DB::table('security_users')->updateOrInsert(
            ['id' => $parentId],
            ['email' => 'parent@example.com']
        );

        // Create student
        DB::table('security_users')->updateOrInsert(
            ['id' => $this->studentId],
            ['first_name' => 'John', 'last_name' => 'Doe']
        );

        // Create 3 absences
        for ($i = 0; $i < 3; $i++) {
            InstitutionStudentAbsenceDetails::create([
                'student_id' => $this->studentId,
                'institution_id' => $this->institutionId,
                'academic_period_id' => $this->academicPeriodId,
                'institution_class_id' => $this->institutionClassId,
                'date' => now()->subDays($i)->toDateString(),
                'period' => 1,
                'subject_id' => 1,
                'absence_type_id' => 1,
            ]);
        }

        // Verify retry_count is set
        $alert = DB::table('alerts_queue')
            ->where('alert_type', 'StudentAttendance')
            ->first();

        $this->assertNotNull($alert);
        $this->assertEquals(0, $alert->retry_count);
    }
}
