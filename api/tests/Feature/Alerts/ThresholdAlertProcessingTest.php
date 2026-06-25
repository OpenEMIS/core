<?php

namespace Tests\Feature\Alerts;

use App\Models\Api5\InstitutionStudentAbsenceDetails;
use App\Models\Api5\InstitutionStudentAdmission;
use App\Models\Api5\InstitutionStudentEnrolment;
use App\Models\Api5\InstitutionStudents;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ThresholdAlertProcessingTest extends TestCase
{
    private const INSTITUTION_ID = 101;
    private const ACADEMIC_PERIOD_ID = 201;
    private const EDUCATION_GRADE_ID = 301;
    private const INSTITUTION_CLASS_ID = 401;

    private const STUDENT_ID = 1001;
    private const GUARDIAN_ID = 1002;
    private const TEACHER_ID = 1003;

    private const ADMISSION_STATUS_ID = 501;
    private const ENROLMENT_STATUS_ID = 502;
    private const STUDENT_STATUS_ID = 503;

    private static bool $schemaReady = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureSchema();
        $this->resetData();
        $this->seedReferenceData();
    }

    /** @test */
    public function student_absence_model_create_triggers_alert_queue_with_resolved_placeholders(): void
    {
        $this->seedAbsenceAlertRule();

        InstitutionStudentAbsenceDetails::create([
            'student_id' => self::STUDENT_ID,
            'institution_id' => self::INSTITUTION_ID,
            'academic_period_id' => self::ACADEMIC_PERIOD_ID,
            'institution_class_id' => self::INSTITUTION_CLASS_ID,
            'education_grade_id' => self::EDUCATION_GRADE_ID,
            'date' => '2026-04-15',
            'period' => 1,
            'absence_type_id' => 1,
            'subject_id' => 11,
            'created_user_id' => 1,
            'created' => '2026-04-15 08:00:00',
            'modified_user_id' => 1,
            'modified' => '2026-04-15 08:00:00',
        ]);

        $this->assertProcessCompleted('StudentAttendance', 'AlertStudentAbsence');
        $queueRows = $this->assertQueuedRecipients('StudentAttendance', [
            'Teacher Alert <teacher.alert@gmail.comz>',
            '99890123zz',
        ]);

        $message = (string) $queueRows[0]->message_body;
        $this->assertStringNotContainsString('${', $message);
        $this->assertStringContainsString('Student Name=Student Alert', $message);
        $this->assertStringContainsString('Student Email=student.alert@gmail.comz', $message);
        $this->assertStringContainsString('Student Postal=120100', $message);
        $this->assertStringContainsString('Student DOB=2011-05-17', $message);
        $this->assertStringContainsString('Student Gender=Female', $message);
        $this->assertStringContainsString('Student Main Nationality=Uzbek', $message);
        $this->assertStringContainsString('Student Identity Type=National ID', $message);
        $this->assertStringContainsString('Institution Name=Codex Test School', $message);
        $this->assertStringContainsString('Institution Website=https://school.test', $message);
        $this->assertStringContainsString('Total Days=1', $message);
        $this->assertStringContainsString('Threshold=1', $message);
    }

    /** @test */
    public function student_admission_model_create_triggers_alert_queue_with_student_and_guardian_recipients(): void
    {
        $this->seedAdmissionAlertRule();

        InstitutionStudentAdmission::create([
            'id' => 6001,
            'start_date' => '2026-04-15',
            'end_date' => '2026-05-15',
            'student_id' => self::STUDENT_ID,
            'status_id' => self::ADMISSION_STATUS_ID,
            'institution_id' => self::INSTITUTION_ID,
            'academic_period_id' => self::ACADEMIC_PERIOD_ID,
            'education_grade_id' => self::EDUCATION_GRADE_ID,
            'institution_class_id' => self::INSTITUTION_CLASS_ID,
        ]);

        $this->assertProcessCompleted('StudentAdmission', 'AlertStudentAdmission');
        $queueRows = $this->assertQueuedRecipients('StudentAdmission', [
            'Student Alert <student.alert@gmail.comz>',
            'Guardian Alert <guardian.alert@gmail.comz>',
            '99890001zz',
            '99890002zz',
        ]);

        $message = (string) $queueRows[0]->message_body;
        $this->assertStringNotContainsString('${', $message);
        $this->assertStringContainsString('Academic Period=AY2026', $message);
        $this->assertStringContainsString('Admission Status=Admission Approved', $message);
        $this->assertStringContainsString('Student Name=Student Alert', $message);
        $this->assertStringContainsString('Student Address=Student Street 1', $message);
        $this->assertStringContainsString('Student Postal=120100', $message);
        $this->assertStringContainsString('Student DOB=2011-05-17', $message);
        $this->assertStringContainsString('Grade=Grade 7', $message);
        $this->assertStringContainsString('Guardian Name=Guardian Alert', $message);
        $this->assertStringContainsString('Guardian Relation=Father', $message);
        $this->assertStringContainsString('Guardian Contact=guardian-contact@gmail.comz', $message);
    }

    /** @test */
    public function student_enrolment_model_create_triggers_alert_queue_with_student_and_guardian_recipients(): void
    {
        $this->seedEnrolmentAlertRule();

        InstitutionStudentEnrolment::create([
            'id' => 7001,
            'start_date' => '2026-06-01',
            'end_date' => '2026-12-31',
            'student_id' => self::STUDENT_ID,
            'status_id' => self::ENROLMENT_STATUS_ID,
            'institution_id' => self::INSTITUTION_ID,
            'academic_period_id' => self::ACADEMIC_PERIOD_ID,
            'education_grade_id' => self::EDUCATION_GRADE_ID,
            'institution_class_id' => self::INSTITUTION_CLASS_ID,
        ]);

        $this->assertProcessCompleted('StudentEnrolment', 'AlertStudentEnrolment');
        $queueRows = $this->assertQueuedRecipients('StudentEnrolment', [
            'Student Alert <student.alert@gmail.comz>',
            'Guardian Alert <guardian.alert@gmail.comz>',
            '99890001zz',
            '99890002zz',
        ]);

        $message = (string) $queueRows[0]->message_body;
        $this->assertStringNotContainsString('${', $message);
        $this->assertStringContainsString('Academic Period=AY2026', $message);
        $this->assertStringContainsString('Enrolment Status=Enrolment Confirmed', $message);
        $this->assertStringContainsString('Student Preferred=Stu', $message);
        $this->assertStringContainsString('Institution Code=SCH-101', $message);
        $this->assertStringContainsString('Institution Contact=Admin One', $message);
        $this->assertStringContainsString('Guardian Name=Guardian Alert', $message);
        $this->assertStringContainsString('Guardian Relation=Father', $message);
        $this->assertStringContainsString('Guardian Contact=guardian-contact@gmail.comz', $message);
    }

    /** @test */
    public function institution_students_model_create_triggers_status_alert_with_uuid_entity_id(): void
    {
        $this->seedStudentStatusAlertRule();

        $institutionStudent = InstitutionStudents::create([
            'id' => '12345',
            'student_id' => self::STUDENT_ID,
            'student_status_id' => self::STUDENT_STATUS_ID,
            'education_grade_id' => self::EDUCATION_GRADE_ID,
            'academic_period_id' => self::ACADEMIC_PERIOD_ID,
            'start_date' => '2026-04-15',
            'start_year' => 2026,
            'institution_id' => self::INSTITUTION_ID,
            'created_user_id' => 1,
            'created' => '2026-04-15 11:00:00',
            'modified_user_id' => 1,
            'modified' => '2026-04-15 11:00:00',
        ]);

        $this->assertNotEmpty($institutionStudent->id);
        $this->assertSame('12345', $institutionStudent->id);

        $this->assertProcessCompleted('StudentStatus', 'AlertStudentStatus');
        $queueRows = $this->assertQueuedRecipients('StudentStatus', [
            'Student Alert <student.alert@gmail.comz>',
            'Guardian Alert <guardian.alert@gmail.comz>',
            '99890001zz',
            '99890002zz',
        ]);

        $process = DB::table('system_processes')->where('name', 'StudentStatus')->first();
        $this->assertNotNull($process);
        $this->assertStringContainsString('"entity_id":"12345"', (string) $process->params);

        $message = (string) $queueRows[0]->message_body;
        $this->assertStringNotContainsString('${', $message);
        $this->assertStringContainsString('Student Status=Active', $message);
        $this->assertStringContainsString('Student Name=Student Alert', $message);
        $this->assertStringContainsString('Student Address=Student Street 1', $message);
        $this->assertStringContainsString('Student Postal=120100', $message);
        $this->assertStringContainsString('Student DOB=2011-05-17', $message);
        $this->assertStringContainsString('Guardian Name=Guardian Alert', $message);
        $this->assertStringContainsString('Guardian Contact=guardian.alert@gmail.comz (email), 99890002zz (mobile)', $message);
    }

    private function ensureSchema(): void
    {
        if (self::$schemaReady) {
            return;
        }

        $this->createTableIfMissing('alerts', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name')->unique();
            $table->string('process_name')->nullable();
            $table->string('frequency')->default('Once');
            $table->integer('process_id')->nullable();
            $table->dateTime('created')->nullable();
            $table->dateTime('modified')->nullable();
        });
        $this->ensureColumn('alerts', 'process_name', fn (Blueprint $table) => $table->string('process_name')->nullable());
        $this->ensureColumn('alerts', 'frequency', fn (Blueprint $table) => $table->string('frequency')->default('Once'));
        $this->ensureColumn('alerts', 'process_id', fn (Blueprint $table) => $table->integer('process_id')->nullable());
        $this->ensureColumn('alerts', 'created', fn (Blueprint $table) => $table->dateTime('created')->nullable());
        $this->ensureColumn('alerts', 'modified', fn (Blueprint $table) => $table->dateTime('modified')->nullable());

        $this->createTableIfMissing('alert_rules', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('feature');
            $table->text('subject');
            $table->longText('message');
            $table->string('method')->default('email');
            $table->text('threshold')->nullable();
            $table->boolean('enabled')->default(true);
            $table->dateTime('created')->nullable();
            $table->dateTime('modified')->nullable();
        });
        $this->ensureColumn('alert_rules', 'message', fn (Blueprint $table) => $table->longText('message'));
        $this->ensureColumn('alert_rules', 'method', fn (Blueprint $table) => $table->string('method')->default('email'));
        $this->ensureColumn('alert_rules', 'threshold', fn (Blueprint $table) => $table->text('threshold')->nullable());
        $this->ensureColumn('alert_rules', 'enabled', fn (Blueprint $table) => $table->boolean('enabled')->default(true));
        $this->ensureColumn('alert_rules', 'created', fn (Blueprint $table) => $table->dateTime('created')->nullable());
        $this->ensureColumn('alert_rules', 'modified', fn (Blueprint $table) => $table->dateTime('modified')->nullable());

        $this->createTableIfMissing('alerts_roles', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('alert_rule_id');
            $table->unsignedInteger('security_role_id');
            $table->dateTime('created')->nullable();
            $table->dateTime('modified')->nullable();
        });

        $this->createTableIfMissing('security_roles', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('name');
            $table->dateTime('created')->nullable();
            $table->dateTime('modified')->nullable();
        });

        $this->createTableIfMissing('security_users', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('openemis_no')->nullable();
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('third_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('preferred_name')->nullable();
            $table->string('email')->nullable();
            $table->string('mobile_number')->nullable();
            $table->string('address')->nullable();
            $table->string('postal_code')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('identity_number')->nullable();
            $table->integer('gender_id')->nullable();
            $table->integer('nationality_id')->nullable();
            $table->integer('identity_type_id')->nullable();
            $table->boolean('status')->default(true);
        });
        $this->ensureColumn('security_users', 'openemis_no', fn (Blueprint $table) => $table->string('openemis_no')->nullable());
        $this->ensureColumn('security_users', 'first_name', fn (Blueprint $table) => $table->string('first_name')->nullable());
        $this->ensureColumn('security_users', 'middle_name', fn (Blueprint $table) => $table->string('middle_name')->nullable());
        $this->ensureColumn('security_users', 'third_name', fn (Blueprint $table) => $table->string('third_name')->nullable());
        $this->ensureColumn('security_users', 'last_name', fn (Blueprint $table) => $table->string('last_name')->nullable());
        $this->ensureColumn('security_users', 'preferred_name', fn (Blueprint $table) => $table->string('preferred_name')->nullable());
        $this->ensureColumn('security_users', 'email', fn (Blueprint $table) => $table->string('email')->nullable());
        $this->ensureColumn('security_users', 'mobile_number', fn (Blueprint $table) => $table->string('mobile_number')->nullable());
        $this->ensureColumn('security_users', 'address', fn (Blueprint $table) => $table->string('address')->nullable());
        $this->ensureColumn('security_users', 'postal_code', fn (Blueprint $table) => $table->string('postal_code')->nullable());
        $this->ensureColumn('security_users', 'date_of_birth', fn (Blueprint $table) => $table->date('date_of_birth')->nullable());
        $this->ensureColumn('security_users', 'identity_number', fn (Blueprint $table) => $table->string('identity_number')->nullable());
        $this->ensureColumn('security_users', 'gender_id', fn (Blueprint $table) => $table->integer('gender_id')->nullable());
        $this->ensureColumn('security_users', 'nationality_id', fn (Blueprint $table) => $table->integer('nationality_id')->nullable());
        $this->ensureColumn('security_users', 'identity_type_id', fn (Blueprint $table) => $table->integer('identity_type_id')->nullable());
        $this->ensureColumn('security_users', 'status', fn (Blueprint $table) => $table->boolean('status')->default(true));

        $this->createTableIfMissing('student_guardians', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('student_id');
            $table->integer('guardian_id');
            $table->integer('guardian_relation_id')->nullable();
        });

        $this->createTableIfMissing('guardian_relations', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('name');
            $table->integer('order')->nullable();
        });

        $this->createTableIfMissing('user_contacts', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('security_user_id');
            $table->string('value');
            $table->boolean('preferred')->default(false);
        });

        $this->createTableIfMissing('institutions', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->integer('security_group_id')->nullable();
            $table->integer('area_id')->nullable();
            $table->integer('classification')->default(1);
            $table->string('name')->nullable();
            $table->string('code')->nullable();
            $table->string('address')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('telephone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
        });
        $this->ensureColumn('institutions', 'security_group_id', fn (Blueprint $table) => $table->integer('security_group_id')->nullable());
        $this->ensureColumn('institutions', 'area_id', fn (Blueprint $table) => $table->integer('area_id')->nullable());
        $this->ensureColumn('institutions', 'classification', fn (Blueprint $table) => $table->integer('classification')->default(1));
        $this->ensureColumn('institutions', 'name', fn (Blueprint $table) => $table->string('name')->nullable());
        $this->ensureColumn('institutions', 'code', fn (Blueprint $table) => $table->string('code')->nullable());
        $this->ensureColumn('institutions', 'address', fn (Blueprint $table) => $table->string('address')->nullable());
        $this->ensureColumn('institutions', 'postal_code', fn (Blueprint $table) => $table->string('postal_code')->nullable());
        $this->ensureColumn('institutions', 'contact_person', fn (Blueprint $table) => $table->string('contact_person')->nullable());
        $this->ensureColumn('institutions', 'telephone', fn (Blueprint $table) => $table->string('telephone')->nullable());
        $this->ensureColumn('institutions', 'email', fn (Blueprint $table) => $table->string('email')->nullable());
        $this->ensureColumn('institutions', 'website', fn (Blueprint $table) => $table->string('website')->nullable());

        $this->createTableIfMissing('security_group_users', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('security_group_id');
            $table->integer('security_user_id');
            $table->integer('security_role_id');
        });

        $this->createTableIfMissing('security_group_institutions', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('security_group_id');
            $table->integer('institution_id');
        });

        $this->createTableIfMissing('institution_classes', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->integer('staff_id')->nullable();
        });

        $this->createTableIfMissing('institution_classes_secondary_staff', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('institution_class_id');
            $table->integer('secondary_staff_id');
        });

        $this->createTableIfMissing('academic_periods', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('name');
        });

        $this->createTableIfMissing('education_grades', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('name');
        });

        $this->createTableIfMissing('workflow_steps', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('name');
        });

        $this->createTableIfMissing('student_statuses', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('name');
        });

        $this->createTableIfMissing('genders', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('name');
        });

        $this->createTableIfMissing('nationalities', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('name');
        });

        $this->createTableIfMissing('identity_types', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->string('name');
        });

        $this->createTableIfMissing('institution_student_absence_details', function (Blueprint $table): void {
            $table->integer('student_id');
            $table->integer('institution_id');
            $table->integer('academic_period_id');
            $table->integer('institution_class_id')->nullable();
            $table->integer('education_grade_id')->nullable();
            $table->date('date');
            $table->integer('period')->default(1);
            $table->string('comment')->nullable();
            $table->integer('absence_type_id');
            $table->integer('student_absence_reason_id')->nullable();
            $table->integer('subject_id')->nullable();
            $table->integer('modified_user_id')->nullable();
            $table->dateTime('modified')->nullable();
            $table->integer('created_user_id')->nullable();
            $table->dateTime('created')->nullable();
        });

        $this->createTableIfMissing('institution_student_admission', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('student_id');
            $table->integer('status_id');
            $table->integer('assignee_id')->nullable();
            $table->integer('institution_id');
            $table->integer('academic_period_id');
            $table->integer('education_grade_id');
            $table->integer('institution_class_id')->nullable();
            $table->integer('test_score')->nullable();
            $table->integer('interview_score')->nullable();
            $table->string('comment')->nullable();
            $table->integer('modified_user_id')->nullable();
            $table->dateTime('modified')->nullable();
            $table->integer('created_user_id')->nullable();
            $table->dateTime('created')->nullable();
        });

        $this->createTableIfMissing('institution_student_enrolment', function (Blueprint $table): void {
            $table->integer('id')->primary();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('student_id');
            $table->integer('status_id');
            $table->integer('assignee_id')->nullable();
            $table->integer('institution_id');
            $table->integer('academic_period_id');
            $table->integer('education_grade_id');
            $table->integer('institution_class_id')->nullable();
            $table->string('comment')->nullable();
            $table->integer('modified_user_id')->nullable();
            $table->dateTime('modified')->nullable();
            $table->integer('created_user_id')->nullable();
            $table->dateTime('created')->nullable();
        });

        $this->createTableIfMissing('institution_students', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->integer('student_status_id');
            $table->integer('student_id');
            $table->integer('education_grade_id');
            $table->integer('academic_period_id');
            $table->date('start_date')->nullable();
            $table->integer('start_year')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('end_year')->nullable();
            $table->integer('institution_id');
            $table->string('previous_institution_student_id')->nullable();
            $table->integer('modified_user_id')->nullable();
            $table->dateTime('modified')->nullable();
            $table->integer('created_user_id')->nullable();
            $table->dateTime('created')->nullable();
        });

        $this->createTableIfMissing('alert_queue', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('alert_type');
            $table->string('channel');
            $table->string('recipient');
            $table->text('subject')->nullable();
            $table->longText('message_body');
            $table->longText('payload')->nullable();
            $table->integer('status')->default(0);
            $table->integer('retry_count')->default(0);
            $table->dateTime('available_at')->nullable();
            $table->dateTime('created')->nullable();
            $table->dateTime('modified')->nullable();
        });
        $this->ensureColumn('alert_queue', 'subject', fn (Blueprint $table) => $table->text('subject')->nullable());
        $this->ensureColumn('alert_queue', 'message_body', fn (Blueprint $table) => $table->longText('message_body'));
        $this->ensureColumn('alert_queue', 'payload', fn (Blueprint $table) => $table->longText('payload')->nullable());
        $this->ensureColumn('alert_queue', 'status', fn (Blueprint $table) => $table->integer('status')->default(0));
        $this->ensureColumn('alert_queue', 'retry_count', fn (Blueprint $table) => $table->integer('retry_count')->default(0));
        $this->ensureColumn('alert_queue', 'available_at', fn (Blueprint $table) => $table->dateTime('available_at')->nullable());
        $this->ensureColumn('alert_queue', 'created', fn (Blueprint $table) => $table->dateTime('created')->nullable());
        $this->ensureColumn('alert_queue', 'modified', fn (Blueprint $table) => $table->dateTime('modified')->nullable());

        $this->createTableIfMissing('system_processes', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name');
            $table->integer('status');
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->string('model');
            $table->integer('created_user_id')->nullable();
            $table->integer('modified_user_id')->nullable();
            $table->dateTime('created')->nullable();
            $table->dateTime('modified')->nullable();
            $table->longText('params')->nullable();
        });
        $this->ensureColumn('system_processes', 'params', fn (Blueprint $table) => $table->longText('params')->nullable());
        $this->ensureColumn('system_processes', 'start_date', fn (Blueprint $table) => $table->dateTime('start_date')->nullable());
        $this->ensureColumn('system_processes', 'end_date', fn (Blueprint $table) => $table->dateTime('end_date')->nullable());
        $this->ensureColumn('system_processes', 'created', fn (Blueprint $table) => $table->dateTime('created')->nullable());
        $this->ensureColumn('system_processes', 'modified', fn (Blueprint $table) => $table->dateTime('modified')->nullable());

        $this->createTableIfMissing('system_errors', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('code')->nullable();
            $table->longText('error_message')->nullable();
            $table->string('request_method')->nullable();
            $table->string('request_url')->nullable();
            $table->string('referrer_url')->nullable();
            $table->string('client_ip')->nullable();
            $table->string('client_browser')->nullable();
            $table->string('triggered_from')->nullable();
            $table->longText('stack_trace')->nullable();
            $table->longText('server_info')->nullable();
            $table->integer('created_user_id')->nullable();
            $table->dateTime('created')->nullable();
        });

        self::$schemaReady = true;
    }

    private function createTableIfMissing(string $table, callable $definition): void
    {
        if (!Schema::hasTable($table)) {
            Schema::create($table, $definition);
        }
    }

    private function ensureColumn(string $table, string $column, callable $definition): void
    {
        if (Schema::hasTable($table) && !Schema::hasColumn($table, $column)) {
            Schema::table($table, $definition);
        }
    }

    private function resetData(): void
    {
        foreach ([
            'alerts_roles',
            'alert_rules',
            'alerts',
            'alert_queue',
            'system_processes',
            'system_errors',
            'institution_student_absence_details',
            'institution_student_admission',
            'institution_student_enrolment',
            'institution_students',
            'student_guardians',
            'user_contacts',
            'guardian_relations',
            'security_group_institutions',
            'security_group_users',
            'institution_classes_secondary_staff',
            'institution_classes',
            'security_users',
            'security_roles',
            'institutions',
            'academic_periods',
            'education_grades',
            'workflow_steps',
            'student_statuses',
            'genders',
            'nationalities',
            'identity_types',
        ] as $table) {
            DB::table($table)->delete();
        }
    }

    private function seedReferenceData(): void
    {
        DB::table('security_roles')->insert([
            ['id' => 1, 'name' => 'Teacher'],
            ['id' => 8, 'name' => 'Student'],
            ['id' => 9, 'name' => 'Guardian'],
        ]);

        DB::table('genders')->insert([
            ['id' => 1, 'name' => 'Female'],
        ]);

        DB::table('nationalities')->insert([
            ['id' => 1, 'name' => 'Uzbek'],
        ]);

        DB::table('identity_types')->insert([
            ['id' => 1, 'name' => 'National ID'],
        ]);

        DB::table('institutions')->insert([
            'id' => self::INSTITUTION_ID,
            'security_group_id' => 21,
            'area_id' => 31,
            'classification' => 1,
            'name' => 'Codex Test School',
            'code' => 'SCH-101',
            'address' => 'Institution Street 9',
            'postal_code' => '120200',
            'contact_person' => 'Admin One',
            'telephone' => '+998700000001',
            'email' => 'school.alerts@gmail.comz',
            'website' => 'https://school.test',
        ]);

        DB::table('academic_periods')->insert([
            'id' => self::ACADEMIC_PERIOD_ID,
            'name' => 'AY2026',
        ]);

        DB::table('education_grades')->insert([
            'id' => self::EDUCATION_GRADE_ID,
            'name' => 'Grade 7',
        ]);

        DB::table('workflow_steps')->insert([
            ['id' => self::ADMISSION_STATUS_ID, 'name' => 'Admission Approved'],
            ['id' => self::ENROLMENT_STATUS_ID, 'name' => 'Enrolment Confirmed'],
        ]);

        DB::table('student_statuses')->insert([
            ['id' => self::STUDENT_STATUS_ID, 'name' => 'Active'],
        ]);

        DB::table('guardian_relations')->insert([
            'id' => 1,
            'name' => 'Father',
            'order' => 1,
        ]);

        DB::table('security_users')->insert([
            [
                'id' => 1,
                'openemis_no' => 'SYS-1',
                'first_name' => 'System',
                'middle_name' => null,
                'third_name' => null,
                'last_name' => 'User',
                'preferred_name' => null,
                'email' => 'system.user@gmail.comz',
                'mobile_number' => '99890000zz',
                'address' => null,
                'postal_code' => null,
                'date_of_birth' => null,
                'identity_number' => null,
                'gender_id' => null,
                'nationality_id' => null,
                'identity_type_id' => null,
                'status' => 1,
            ],
            [
                'id' => self::STUDENT_ID,
                'openemis_no' => 'STU-1001',
                'first_name' => 'Student',
                'middle_name' => null,
                'third_name' => null,
                'last_name' => 'Alert',
                'preferred_name' => 'Stu',
                'email' => 'student.alert@gmail.comz',
                'mobile_number' => '99890001zz',
                'address' => 'Student Street 1',
                'postal_code' => '120100',
                'date_of_birth' => '2011-05-17',
                'identity_number' => 'AA1234567',
                'gender_id' => 1,
                'nationality_id' => 1,
                'identity_type_id' => 1,
                'status' => 1,
            ],
            [
                'id' => self::GUARDIAN_ID,
                'openemis_no' => 'GUA-1002',
                'first_name' => 'Guardian',
                'middle_name' => null,
                'third_name' => null,
                'last_name' => 'Alert',
                'preferred_name' => null,
                'email' => 'guardian.alert@gmail.comz',
                'mobile_number' => '99890002zz',
                'address' => null,
                'postal_code' => null,
                'date_of_birth' => null,
                'identity_number' => null,
                'gender_id' => null,
                'nationality_id' => null,
                'identity_type_id' => null,
                'status' => 1,
            ],
            [
                'id' => self::TEACHER_ID,
                'openemis_no' => 'TCH-1003',
                'first_name' => 'Teacher',
                'middle_name' => null,
                'third_name' => null,
                'last_name' => 'Alert',
                'preferred_name' => null,
                'email' => 'teacher.alert@gmail.comz',
                'mobile_number' => '99890123zz',
                'address' => null,
                'postal_code' => null,
                'date_of_birth' => null,
                'identity_number' => null,
                'gender_id' => null,
                'nationality_id' => null,
                'identity_type_id' => null,
                'status' => 1,
            ],
        ]);

        DB::table('student_guardians')->insert([
            'student_id' => self::STUDENT_ID,
            'guardian_id' => self::GUARDIAN_ID,
            'guardian_relation_id' => 1,
        ]);

        DB::table('user_contacts')->insert([
            'security_user_id' => self::GUARDIAN_ID,
            'value' => 'guardian-contact@gmail.comz',
            'preferred' => 1,
        ]);

        DB::table('institution_classes')->insert([
            'id' => self::INSTITUTION_CLASS_ID,
            'staff_id' => self::TEACHER_ID,
        ]);
    }

    private function seedAbsenceAlertRule(): void
    {
        $ruleId = $this->insertAlertRule(
            'StudentAttendance',
            'AlertStudentAbsence',
            'email,sms',
            '1',
            'Absence placeholders for ${student.name}',
            'Student Name=${student.name}; Student OpenEMIS=${student.openemis_no}; Student First=${student.first_name}; Student Middle=${student.middle_name}; Student Third=${student.third_name}; Student Last=${student.last_name}; Student Preferred=${student.preferred_name}; Student Email=${student.email}; Student Address=${student.address}; Student Postal=${student.postal_code}; Student DOB=${student.date_of_birth}; Student Gender=${student.gender}; Student Identity Number=${student.identity_number}; Student Main Nationality=${student.main_nationality}; Student Identity Type=${student.identity_type}; Institution Name=${institution.name}; Institution Code=${institution.code}; Institution Address=${institution.address}; Institution Postal=${institution.postal_code}; Institution Contact Person=${institution.contact_person}; Institution Telephone=${institution.telephone}; Institution Email=${institution.email}; Institution Website=${institution.website}; Total Days=${total_days}; Total Times=${total_times}; Threshold=${threshold}'
        );

        DB::table('alerts_roles')->insert([
            'alert_rule_id' => $ruleId,
            'security_role_id' => 1,
        ]);
    }

    private function seedAdmissionAlertRule(): void
    {
        $ruleId = $this->insertAlertRule(
            'StudentAdmission',
            'AlertStudentAdmission',
            'email,sms',
            json_encode(['workflow_steps' => [self::ADMISSION_STATUS_ID]]),
            'Admission placeholders for ${student.name}',
            'Academic Period=${academic_period.name}; Start=${start_date}; End=${end_date}; Admission Status=${admission_status}; Student Name=${student.name}; Student OpenEMIS=${student.openemis_no}; Student First=${student.first_name}; Student Middle=${student.middle_name}; Student Third=${student.third_name}; Student Last=${student.last_name}; Student Preferred=${student.preferred_name}; Student Email=${student.email}; Student Address=${student.address}; Student Postal=${student.postal_code}; Student DOB=${student.date_of_birth}; Institution Name=${institution.name}; Institution Code=${institution.code}; Institution Address=${institution.address}; Institution Postal=${institution.postal_code}; Institution Contact=${institution.contact_person}; Institution Telephone=${institution.telephone}; Institution Email=${institution.email}; Institution Website=${institution.website}; Grade=${grade.name}; Guardian Name=${guardian.name}; Guardian Relation=${guardian.relation}; Guardian Contact=${guardian.contact}'
        );

        DB::table('alerts_roles')->insert([
            ['alert_rule_id' => $ruleId, 'security_role_id' => 8],
            ['alert_rule_id' => $ruleId, 'security_role_id' => 9],
        ]);
    }

    private function seedEnrolmentAlertRule(): void
    {
        $ruleId = $this->insertAlertRule(
            'StudentEnrolment',
            'AlertStudentEnrolment',
            'email,sms',
            json_encode(['workflow_steps' => [self::ENROLMENT_STATUS_ID]]),
            'Enrolment placeholders for ${student.name}',
            'Academic Period=${academic_period.name}; Start=${start_date}; End=${end_date}; Enrolment Status=${enrolment_status}; Student Name=${student.name}; Student OpenEMIS=${student.openemis_no}; Student First=${student.first_name}; Student Middle=${student.middle_name}; Student Third=${student.third_name}; Student Last=${student.last_name}; Student Preferred=${student.preferred_name}; Student Email=${student.email}; Student Address=${student.address}; Student Postal=${student.postal_code}; Student DOB=${student.date_of_birth}; Institution Name=${institution.name}; Institution Code=${institution.code}; Institution Address=${institution.address}; Institution Postal=${institution.postal_code}; Institution Contact=${institution.contact_person}; Institution Telephone=${institution.telephone}; Institution Email=${institution.email}; Institution Website=${institution.website}; Grade=${grade.name}; Guardian Name=${guardian.name}; Guardian Relation=${guardian.relation}; Guardian Contact=${guardian.contact}'
        );

        DB::table('alerts_roles')->insert([
            ['alert_rule_id' => $ruleId, 'security_role_id' => 8],
            ['alert_rule_id' => $ruleId, 'security_role_id' => 9],
        ]);
    }

    private function seedStudentStatusAlertRule(): void
    {
        $ruleId = $this->insertAlertRule(
            'StudentStatus',
            'AlertStudentStatus',
            'email,sms',
            json_encode(['statuses' => [self::STUDENT_STATUS_ID]]),
            'Student status placeholders for ${student.name}',
            'Academic Period=${academic_period.name}; Start=${start_date}; End=${end_date}; Student Status=${student_status}; Student Name=${student.name}; Student OpenEMIS=${student.openemis_no}; Student First=${student.first_name}; Student Middle=${student.middle_name}; Student Third=${student.third_name}; Student Last=${student.last_name}; Student Preferred=${student.preferred_name}; Student Email=${student.email}; Student Address=${student.address}; Student Postal=${student.postal_code}; Student DOB=${student.date_of_birth}; Institution Name=${institution.name}; Institution Code=${institution.code}; Institution Address=${institution.address}; Institution Postal=${institution.postal_code}; Institution Contact=${institution.contact_person}; Institution Telephone=${institution.telephone}; Institution Email=${institution.email}; Institution Website=${institution.website}; Grade=${grade.name}; Guardian Name=${guardian.name}; Guardian Relation=${guardian.relation}; Guardian Contact=${guardian.contact}'
        );

        DB::table('alerts_roles')->insert([
            ['alert_rule_id' => $ruleId, 'security_role_id' => 8],
            ['alert_rule_id' => $ruleId, 'security_role_id' => 9],
        ]);
    }

    private function insertAlertRule(
        string $feature,
        string $processName,
        string $method,
        string $threshold,
        string $subject,
        string $message
    ): int {
        DB::table('alerts')->insert([
            'name' => $feature,
            'process_name' => $processName,
            'frequency' => 'Once',
            'created' => now(),
            'modified' => now(),
        ]);

        return DB::table('alert_rules')->insertGetId([
            'feature' => $feature,
            'subject' => $subject,
            'message' => $message,
            'method' => $method,
            'threshold' => $threshold,
            'enabled' => 1,
            'created' => now(),
            'modified' => now(),
        ]);
    }

    private function assertProcessCompleted(string $feature, string $processName): void
    {
        $process = DB::table('system_processes')
            ->where('name', $feature)
            ->where('model', $processName)
            ->first();

        $this->assertNotNull($process);
        $this->assertSame(3, (int) $process->status);
        $this->assertStringNotContainsString('${', (string) $process->params);
    }

    /**
     * @return array<int, object>
     */
    private function assertQueuedRecipients(string $feature, array $expectedRecipients): array
    {
        $queueRows = DB::table('alert_queue')
            ->where('alert_type', $feature)
            ->orderBy('id')
            ->get()
            ->all();

        $this->assertCount(count($expectedRecipients), $queueRows);

        $actualRecipients = array_map(static fn ($row) => $row->recipient, $queueRows);
        sort($actualRecipients);
        sort($expectedRecipients);
        $this->assertSame($expectedRecipients, $actualRecipients);

        foreach ($queueRows as $row) {
            $this->assertSame(0, (int) $row->status);
            $this->assertStringNotContainsString('${', (string) $row->subject);
            $this->assertStringNotContainsString('${', (string) $row->message_body);
        }

        return $queueRows;
    }
}
