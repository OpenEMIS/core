<?php

namespace Tests\Feature\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait BootstrapsPocor9509AlertApiData
{
    protected static bool $pocor9509ApiSchemaReady = false;

    protected function bootPocor9509AlertApiData(): void
    {
        $this->ensurePocor9509AlertApiSchema(); //POCOR-9509: keep the 4 alert-related API tests runnable on sparse DBs
        $this->ensurePocor9509AuthUser(); //POCOR-9509: shared JWT bootstrap user for the alert-related API tests
    }

    protected function ensurePocor9509AuthUser(): void
    {
        DB::table('security_groups')->updateOrInsert(
            ['id' => 21],
            ['name' => 'POCOR-9509 Test Group']
        );

        DB::table('security_group_users')->updateOrInsert(
            ['security_group_id' => 21, 'security_user_id' => 2],
            ['security_role_id' => 1]
        );

        DB::table('security_group_institutions')->updateOrInsert(
            ['security_group_id' => 21, 'institution_id' => 101],
            []
        );

        DB::table('security_users')->updateOrInsert(
            ['id' => 2],
            [
                'openemis_no' => 'TEST-2',
                'first_name' => 'Alert',
                'last_name' => 'Tester',
                'preferred_name' => 'Alert Tester',
                'email' => 'alert.tester@gmail.comz',
                'mobile_number' => '99890002zz',
                'super_admin' => 1,
                'status' => 1,
            ]
        );
    }

    protected function ensurePocor9509AlertApiSchema(): void
    {
        if (self::$pocor9509ApiSchemaReady) {
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

        $this->createTableIfMissing('security_users', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
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
            $table->unsignedBigInteger('gender_id')->nullable();
            $table->unsignedBigInteger('nationality_id')->nullable();
            $table->unsignedBigInteger('identity_type_id')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->string('identity_number')->nullable();
        });
        $this->ensureColumn('security_users', 'super_admin', fn (Blueprint $table) => $table->tinyInteger('super_admin')->default(0));

        $this->createTableIfMissing('security_groups', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name')->nullable();
        });

        $this->createTableIfMissing('security_group_users', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedBigInteger('security_group_id');
            $table->unsignedBigInteger('security_user_id');
            $table->unsignedBigInteger('security_role_id')->nullable();
        });

        $this->createTableIfMissing('security_group_institutions', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedBigInteger('security_group_id');
            $table->unsignedBigInteger('institution_id');
        });

        $this->createTableIfMissing('institution_student_absence_details', function (Blueprint $table): void {
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('academic_period_id');
            $table->unsignedBigInteger('institution_class_id')->nullable();
            $table->unsignedBigInteger('education_grade_id')->nullable();
            $table->date('date');
            $table->integer('period');
            $table->string('comment')->nullable();
            $table->unsignedBigInteger('absence_type_id');
            $table->unsignedBigInteger('student_absence_reason_id')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->unsignedBigInteger('modified_user_id')->nullable();
            $table->dateTime('modified')->nullable();
            $table->unsignedBigInteger('created_user_id')->nullable();
            $table->dateTime('created')->nullable();
        });
        $this->ensureColumn('institution_student_absence_details', 'student_id', fn (Blueprint $table) => $table->unsignedBigInteger('student_id'));
        $this->ensureColumn('institution_student_absence_details', 'institution_id', fn (Blueprint $table) => $table->unsignedBigInteger('institution_id'));
        $this->ensureColumn('institution_student_absence_details', 'academic_period_id', fn (Blueprint $table) => $table->unsignedBigInteger('academic_period_id'));
        $this->ensureColumn('institution_student_absence_details', 'institution_class_id', fn (Blueprint $table) => $table->unsignedBigInteger('institution_class_id')->nullable());
        $this->ensureColumn('institution_student_absence_details', 'education_grade_id', fn (Blueprint $table) => $table->unsignedBigInteger('education_grade_id')->nullable());
        $this->ensureColumn('institution_student_absence_details', 'date', fn (Blueprint $table) => $table->date('date'));
        $this->ensureColumn('institution_student_absence_details', 'period', fn (Blueprint $table) => $table->integer('period'));
        $this->ensureColumn('institution_student_absence_details', 'comment', fn (Blueprint $table) => $table->string('comment')->nullable());
        $this->ensureColumn('institution_student_absence_details', 'absence_type_id', fn (Blueprint $table) => $table->unsignedBigInteger('absence_type_id'));
        $this->ensureColumn('institution_student_absence_details', 'student_absence_reason_id', fn (Blueprint $table) => $table->unsignedBigInteger('student_absence_reason_id')->nullable());
        $this->ensureColumn('institution_student_absence_details', 'subject_id', fn (Blueprint $table) => $table->unsignedBigInteger('subject_id')->nullable());
        $this->ensureColumn('institution_student_absence_details', 'modified_user_id', fn (Blueprint $table) => $table->unsignedBigInteger('modified_user_id')->nullable());
        $this->ensureColumn('institution_student_absence_details', 'modified', fn (Blueprint $table) => $table->dateTime('modified')->nullable());
        $this->ensureColumn('institution_student_absence_details', 'created_user_id', fn (Blueprint $table) => $table->unsignedBigInteger('created_user_id')->nullable());
        $this->ensureColumn('institution_student_absence_details', 'created', fn (Blueprint $table) => $table->dateTime('created')->nullable());

        $this->createTableIfMissing('institution_student_admission', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('status_id');
            $table->unsignedBigInteger('assignee_id')->nullable();
            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('academic_period_id');
            $table->unsignedBigInteger('education_grade_id');
            $table->unsignedBigInteger('institution_class_id')->nullable();
            $table->integer('test_score')->nullable();
            $table->integer('interview_score')->nullable();
            $table->string('comment')->nullable();
            $table->unsignedBigInteger('modified_user_id')->nullable();
            $table->dateTime('modified')->nullable();
            $table->unsignedBigInteger('created_user_id')->nullable();
            $table->dateTime('created')->nullable();
        });
        $this->ensureColumn('institution_student_admission', 'start_date', fn (Blueprint $table) => $table->date('start_date')->nullable());
        $this->ensureColumn('institution_student_admission', 'end_date', fn (Blueprint $table) => $table->date('end_date')->nullable());
        $this->ensureColumn('institution_student_admission', 'student_id', fn (Blueprint $table) => $table->unsignedBigInteger('student_id'));
        $this->ensureColumn('institution_student_admission', 'status_id', fn (Blueprint $table) => $table->unsignedBigInteger('status_id'));
        $this->ensureColumn('institution_student_admission', 'assignee_id', fn (Blueprint $table) => $table->unsignedBigInteger('assignee_id')->nullable());
        $this->ensureColumn('institution_student_admission', 'institution_id', fn (Blueprint $table) => $table->unsignedBigInteger('institution_id'));
        $this->ensureColumn('institution_student_admission', 'academic_period_id', fn (Blueprint $table) => $table->unsignedBigInteger('academic_period_id'));
        $this->ensureColumn('institution_student_admission', 'education_grade_id', fn (Blueprint $table) => $table->unsignedBigInteger('education_grade_id'));
        $this->ensureColumn('institution_student_admission', 'institution_class_id', fn (Blueprint $table) => $table->unsignedBigInteger('institution_class_id')->nullable());
        $this->ensureColumn('institution_student_admission', 'test_score', fn (Blueprint $table) => $table->integer('test_score')->nullable());
        $this->ensureColumn('institution_student_admission', 'interview_score', fn (Blueprint $table) => $table->integer('interview_score')->nullable());
        $this->ensureColumn('institution_student_admission', 'comment', fn (Blueprint $table) => $table->string('comment')->nullable());
        $this->ensureColumn('institution_student_admission', 'modified_user_id', fn (Blueprint $table) => $table->unsignedBigInteger('modified_user_id')->nullable());
        $this->ensureColumn('institution_student_admission', 'modified', fn (Blueprint $table) => $table->dateTime('modified')->nullable());
        $this->ensureColumn('institution_student_admission', 'created_user_id', fn (Blueprint $table) => $table->unsignedBigInteger('created_user_id')->nullable());
        $this->ensureColumn('institution_student_admission', 'created', fn (Blueprint $table) => $table->dateTime('created')->nullable());

        $this->createTableIfMissing('institution_student_enrolment', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('status_id');
            $table->unsignedBigInteger('assignee_id')->nullable();
            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('academic_period_id');
            $table->unsignedBigInteger('education_grade_id');
            $table->unsignedBigInteger('institution_class_id')->nullable();
            $table->string('comment')->nullable();
            $table->unsignedBigInteger('modified_user_id')->nullable();
            $table->dateTime('modified')->nullable();
            $table->unsignedBigInteger('created_user_id')->nullable();
            $table->dateTime('created')->nullable();
        });
        $this->ensureColumn('institution_student_enrolment', 'start_date', fn (Blueprint $table) => $table->date('start_date')->nullable());
        $this->ensureColumn('institution_student_enrolment', 'end_date', fn (Blueprint $table) => $table->date('end_date')->nullable());
        $this->ensureColumn('institution_student_enrolment', 'student_id', fn (Blueprint $table) => $table->unsignedBigInteger('student_id'));
        $this->ensureColumn('institution_student_enrolment', 'status_id', fn (Blueprint $table) => $table->unsignedBigInteger('status_id'));
        $this->ensureColumn('institution_student_enrolment', 'assignee_id', fn (Blueprint $table) => $table->unsignedBigInteger('assignee_id')->nullable());
        $this->ensureColumn('institution_student_enrolment', 'institution_id', fn (Blueprint $table) => $table->unsignedBigInteger('institution_id'));
        $this->ensureColumn('institution_student_enrolment', 'academic_period_id', fn (Blueprint $table) => $table->unsignedBigInteger('academic_period_id'));
        $this->ensureColumn('institution_student_enrolment', 'education_grade_id', fn (Blueprint $table) => $table->unsignedBigInteger('education_grade_id'));
        $this->ensureColumn('institution_student_enrolment', 'institution_class_id', fn (Blueprint $table) => $table->unsignedBigInteger('institution_class_id')->nullable());
        $this->ensureColumn('institution_student_enrolment', 'comment', fn (Blueprint $table) => $table->string('comment')->nullable());
        $this->ensureColumn('institution_student_enrolment', 'modified_user_id', fn (Blueprint $table) => $table->unsignedBigInteger('modified_user_id')->nullable());
        $this->ensureColumn('institution_student_enrolment', 'modified', fn (Blueprint $table) => $table->dateTime('modified')->nullable());
        $this->ensureColumn('institution_student_enrolment', 'created_user_id', fn (Blueprint $table) => $table->unsignedBigInteger('created_user_id')->nullable());
        $this->ensureColumn('institution_student_enrolment', 'created', fn (Blueprint $table) => $table->dateTime('created')->nullable());

        $this->createTableIfMissing('institution_students', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->unsignedBigInteger('student_status_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('education_grade_id');
            $table->unsignedBigInteger('academic_period_id');
            $table->date('start_date')->nullable();
            $table->integer('start_year')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('end_year')->nullable();
            $table->unsignedBigInteger('institution_id');
            $table->string('previous_institution_student_id')->nullable();
            $table->unsignedBigInteger('modified_user_id')->nullable();
            $table->dateTime('modified')->nullable();
            $table->unsignedBigInteger('created_user_id')->nullable();
            $table->dateTime('created')->nullable();
        });
        $this->ensureColumn('institution_students', 'student_status_id', fn (Blueprint $table) => $table->unsignedBigInteger('student_status_id'));
        $this->ensureColumn('institution_students', 'student_id', fn (Blueprint $table) => $table->unsignedBigInteger('student_id'));
        $this->ensureColumn('institution_students', 'education_grade_id', fn (Blueprint $table) => $table->unsignedBigInteger('education_grade_id'));
        $this->ensureColumn('institution_students', 'academic_period_id', fn (Blueprint $table) => $table->unsignedBigInteger('academic_period_id'));
        $this->ensureColumn('institution_students', 'start_date', fn (Blueprint $table) => $table->date('start_date')->nullable());
        $this->ensureColumn('institution_students', 'start_year', fn (Blueprint $table) => $table->integer('start_year')->nullable());
        $this->ensureColumn('institution_students', 'end_date', fn (Blueprint $table) => $table->date('end_date')->nullable());
        $this->ensureColumn('institution_students', 'end_year', fn (Blueprint $table) => $table->integer('end_year')->nullable());
        $this->ensureColumn('institution_students', 'institution_id', fn (Blueprint $table) => $table->unsignedBigInteger('institution_id'));
        $this->ensureColumn('institution_students', 'previous_institution_student_id', fn (Blueprint $table) => $table->string('previous_institution_student_id')->nullable());
        $this->ensureColumn('institution_students', 'modified_user_id', fn (Blueprint $table) => $table->unsignedBigInteger('modified_user_id')->nullable());
        $this->ensureColumn('institution_students', 'modified', fn (Blueprint $table) => $table->dateTime('modified')->nullable());
        $this->ensureColumn('institution_students', 'created_user_id', fn (Blueprint $table) => $table->unsignedBigInteger('created_user_id')->nullable());
        $this->ensureColumn('institution_students', 'created', fn (Blueprint $table) => $table->dateTime('created')->nullable());

        self::$pocor9509ApiSchemaReady = true;
    }

    protected function createTableIfMissing(string $table, callable $definition): void
    {
        if (!Schema::hasTable($table)) {
            Schema::create($table, $definition);
        }
    }

    protected function ensureColumn(string $table, string $column, callable $definition): void
    {
        if (Schema::hasTable($table) && !Schema::hasColumn($table, $column)) {
            Schema::table($table, $definition);
        }
    }

    protected function pocor9509AbsencePayload(array $overrides = []): array
    {
        return array_merge([
            'student_id' => 2,
            'institution_id' => 101,
            'academic_period_id' => 201,
            'institution_class_id' => 301,
            'education_grade_id' => 401,
            'date' => '2026-04-15',
            'period' => 1,
            'comment' => 'POCOR-9509 absence test',
            'absence_type_id' => 1,
            'student_absence_reason_id' => 1,
            'subject_id' => 1,
            'modified_user_id' => 2,
            'modified' => '2026-04-15 12:00:00',
            'created_user_id' => 2,
            'created' => '2026-04-15 12:00:00',
        ], $overrides);
    }

    protected function pocor9509AdmissionPayload(array $overrides = []): array
    {
        return array_merge([
            'id' => ((int) DB::table('institution_student_admission')->max('id')) + 1,
            'start_date' => '2026-04-15',
            'end_date' => '2026-05-15',
            'student_id' => 2,
            'status_id' => 1,
            'assignee_id' => 2,
            'institution_id' => 101,
            'academic_period_id' => 201,
            'education_grade_id' => 401,
            'institution_class_id' => 301,
            'test_score' => 80,
            'interview_score' => 85,
            'comment' => 'POCOR-9509 admission test',
            'modified_user_id' => 2,
            'modified' => '2026-04-15 12:00:00',
            'created_user_id' => 2,
            'created' => '2026-04-15 12:00:00',
        ], $overrides);
    }

    protected function pocor9509EnrolmentPayload(array $overrides = []): array
    {
        return array_merge([
            'id' => ((int) DB::table('institution_student_enrolment')->max('id')) + 1,
            'start_date' => '2026-04-15',
            'end_date' => '2026-06-15',
            'student_id' => 2,
            'status_id' => 1,
            'assignee_id' => 2,
            'institution_id' => 101,
            'academic_period_id' => 201,
            'education_grade_id' => 401,
            'institution_class_id' => 301,
            'comment' => 'POCOR-9509 enrolment test',
            'modified_user_id' => 2,
            'modified' => '2026-04-15 12:00:00',
            'created_user_id' => 2,
            'created' => '2026-04-15 12:00:00',
        ], $overrides);
    }

    protected function pocor9509InstitutionStudentPayload(array $overrides = []): array
    {
        return array_merge([
            'student_status_id' => 1,
            'student_id' => 2,
            'education_grade_id' => 401,
            'academic_period_id' => 201,
            'start_date' => '2026-04-15',
            'start_year' => 2026,
            'end_date' => '2026-12-15',
            'end_year' => 2026,
            'institution_id' => 101,
            'previous_institution_student_id' => 'PREV-' . uniqid(),
            'modified_user_id' => 2,
            'modified' => '2026-04-15 12:00:00',
            'created_user_id' => 2,
            'created' => '2026-04-15 12:00:00',
        ], $overrides);
    }
}
