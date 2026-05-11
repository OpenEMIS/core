<?php

namespace Tests\Feature\Alerts;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * POCOR-9509: Base class for scheduled alert command smoke tests.
 *
 * Each subclass:
 *  - declares RULE_ID, FEATURE, ARTISAN_COMMAND, THRESHOLD_JSON
 *  - calls $this->runAlertCommand() in its test
 *
 * What these tests verify:
 *  - Command is registered and wired to the correct artisan name
 *  - prepareContext() succeeds with a seeded rule + role
 *  - Command exits 0 (SUCCESS) gracefully when domain tables are empty
 */
abstract class ScheduledAlertCommandTestCase extends TestCase
{
    // Shared fixture IDs — high enough to avoid collisions with real data
    protected const SHARED_ROLE_ID   = 9001;
    protected const SHARED_USER_ID   = 1;

    // Subclasses must define these
    abstract protected function ruleId(): int;
    abstract protected function feature(): string;
    abstract protected function artisanCommand(): string;
    abstract protected function thresholdJson(): string;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureSchema();
        $this->seedMinimalFixtures();
    }

    protected function tearDown(): void
    {
        DB::table('alerts_roles')->where('alert_rule_id', $this->ruleId())->delete();
        DB::table('alert_rules')->where('id', $this->ruleId())->delete();
        DB::table('security_roles')->where('id', static::SHARED_ROLE_ID)->delete();
        parent::tearDown();
    }

    // ─────────────────────────────────────────────────
    // Shared helpers
    // ─────────────────────────────────────────────────

    protected function runAlertCommand(): \Illuminate\Testing\PendingCommand
    {
        return $this->artisan($this->artisanCommand(), [
            '--user_id'   => static::SHARED_USER_ID,
            '--rule_id'   => $this->ruleId(),
            '--process_id' => 1,
        ]);
    }

    protected function assertNoQueueRowsForFeature(): void
    {
        $count = DB::table('alert_queue')
            ->where('alert_type', $this->feature())
            ->count();

        $this->assertSame(0, $count, "Expected no alert_queue rows for {$this->feature()} but found {$count}");
    }

    // ─────────────────────────────────────────────────
    // Schema / seed helpers
    // ─────────────────────────────────────────────────

    private function ensureSchema(): void
    {
        // security_users — needed by all commands
        if (!Schema::hasTable('security_users')) {
            Schema::create('security_users', function (Blueprint $t) {
                $t->increments('id');
                $t->string('openemis_no')->nullable();
                $t->string('first_name')->nullable();
                $t->string('middle_name')->nullable();
                $t->string('third_name')->nullable();
                $t->string('last_name')->nullable();
                $t->string('preferred_name')->nullable();
                $t->string('email')->nullable();
                $t->string('address')->nullable();
                $t->string('postal_code')->nullable();
                $t->date('date_of_birth')->nullable();
                $t->boolean('super_admin')->default(false);
                $t->integer('status')->default(1);
                $t->dateTime('created')->nullable();
                $t->dateTime('modified')->nullable();
            });
        }

        // institutions — needed by all commands
        if (!Schema::hasTable('institutions')) {
            Schema::create('institutions', function (Blueprint $t) {
                $t->increments('id');
                $t->string('name')->nullable();
                $t->string('code')->nullable();
                $t->string('address')->nullable();
                $t->string('postal_code')->nullable();
                $t->string('contact_person')->nullable();
                $t->string('telephone')->nullable();
                $t->string('email')->nullable();
                $t->string('website')->nullable();
                $t->dateTime('created')->nullable();
                $t->dateTime('modified')->nullable();
            });
        }

        // alert_rules
        if (!Schema::hasTable('alert_rules')) {
            Schema::create('alert_rules', function (Blueprint $t) {
                $t->increments('id');
                $t->string('feature');
                $t->text('subject')->nullable();
                $t->longText('message')->nullable();
                $t->string('method')->default('Email');
                $t->text('threshold')->nullable();
                $t->boolean('enabled')->default(true);
                $t->dateTime('created')->nullable();
                $t->dateTime('modified')->nullable();
            });
        } else {
            foreach (['subject','message','method','threshold','enabled','created','modified'] as $col) {
                if (!Schema::hasColumn('alert_rules', $col)) {
                    Schema::table('alert_rules', function (Blueprint $t) use ($col) {
                        match ($col) {
                            'subject'   => $t->text('subject')->nullable(),
                            'message'   => $t->longText('message')->nullable(),
                            'method'    => $t->string('method')->default('Email'),
                            'threshold' => $t->text('threshold')->nullable(),
                            'enabled'   => $t->boolean('enabled')->default(true),
                            'created'   => $t->dateTime('created')->nullable(),
                            'modified'  => $t->dateTime('modified')->nullable(),
                        };
                    });
                }
            }
        }

        // alerts_roles
        if (!Schema::hasTable('alerts_roles')) {
            Schema::create('alerts_roles', function (Blueprint $t) {
                $t->increments('id');
                $t->unsignedInteger('alert_rule_id');
                $t->unsignedInteger('security_role_id');
                $t->dateTime('created')->nullable();
                $t->dateTime('modified')->nullable();
            });
        }

        // security_roles
        if (!Schema::hasTable('security_roles')) {
            Schema::create('security_roles', function (Blueprint $t) {
                $t->integer('id')->primary();
                $t->string('name');
                $t->dateTime('created')->nullable();
                $t->dateTime('modified')->nullable();
            });
        }

        // alert_queue
        if (!Schema::hasTable('alert_queue')) {
            Schema::create('alert_queue', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->string('feature')->nullable();
                $t->string('method')->nullable();
                $t->text('recipient')->nullable();
                $t->text('subject')->nullable();
                $t->longText('message_body')->nullable();
                $t->tinyInteger('status')->default(0);
                $t->integer('retry_count')->default(0);
                $t->dateTime('created')->nullable();
                $t->dateTime('modified')->nullable();
            });
        }

        $this->ensureCommandDomainSchema();
    }

    private function seedMinimalFixtures(): void
    {
        // Ensure user id=1 exists and is a super admin (no area filtering in commands)
        DB::table('security_users')->updateOrInsert(
            ['id' => static::SHARED_USER_ID],
            ['super_admin' => 1, 'status' => 1, 'first_name' => 'System', 'last_name' => 'Admin']
        );

        // Security role — only id + name columns exist
        DB::table('security_roles')->updateOrInsert(
            ['id' => static::SHARED_ROLE_ID],
            ['name' => 'Scheduled Alert Test Role']
        );

        // Alert rule
        DB::table('alert_rules')->updateOrInsert(
            ['id' => $this->ruleId()],
            [
                'feature'           => $this->feature(),
                'subject'           => 'Test: ' . $this->feature(),
                'message'           => 'Feature=${feature} Rule=${rule_id}',
                'method'            => 'Email',
                'threshold'         => $this->thresholdJson(),
                'enabled'           => 1,
                'created_user_id'   => 1,
                'modified_user_id'  => 1,
                'created'           => now(),
                'modified'          => now(),
            ]
        );

        // Link role to rule
        DB::table('alerts_roles')->updateOrInsert(
            ['alert_rule_id' => $this->ruleId(), 'security_role_id' => static::SHARED_ROLE_ID],
            ['created_user_id' => 1, 'modified_user_id' => 1, 'created' => now(), 'modified' => now()]
        );
    }

    private function ensureCommandDomainSchema(): void
    {
        // workflow_models — needed by AlertStaffLeaveCommand::getApprovedStepIds()
        if (!Schema::hasTable('workflow_models')) {
            Schema::create('workflow_models', function (Blueprint $t) {
                $t->increments('id');
                $t->string('model')->nullable();
                $t->dateTime('created')->nullable();
            });
        }

        // workflows — needed by AlertStaffLeaveCommand::getApprovedStepIds()
        if (!Schema::hasTable('workflows')) {
            Schema::create('workflows', function (Blueprint $t) {
                $t->increments('id');
                $t->unsignedInteger('workflow_model_id')->nullable();
                $t->dateTime('created')->nullable();
            });
        }

        // workflow_steps — needed by AlertStaffLeaveCommand and AlertScholarshipApplicationCommand
        if (!Schema::hasTable('workflow_steps')) {
            Schema::create('workflow_steps', function (Blueprint $t) {
                $t->increments('id');
                $t->unsignedInteger('workflow_id')->nullable();
                $t->string('name')->nullable();
                $t->string('category')->nullable();
                $t->dateTime('created')->nullable();
            });
        } else {
            // POCOR-9509: Ensure required columns exist for failing tests
            foreach (['workflow_id', 'name', 'category', 'created'] as $col) {
                if (!Schema::hasColumn('workflow_steps', $col)) {
                    Schema::table('workflow_steps', function (Blueprint $t) use ($col) {
                        match ($col) {
                            'workflow_id' => $t->unsignedInteger('workflow_id')->nullable(),
                            'name'        => $t->string('name')->nullable(),
                            'category'    => $t->string('category')->nullable(),
                            'created'     => $t->dateTime('created')->nullable(),
                        };
                    });
                }
            }
        }

        // staff_leave_types — needed by AlertStaffLeaveCommand
        if (!Schema::hasTable('staff_leave_types')) {
            Schema::create('staff_leave_types', function (Blueprint $t) {
                $t->increments('id');
                $t->string('name')->nullable();
                $t->dateTime('created')->nullable();
            });
        }

        // institution_staff_leave — needed by AlertStaffLeaveCommand
        if (!Schema::hasTable('institution_staff_leave')) {
            Schema::create('institution_staff_leave', function (Blueprint $t) {
                $t->increments('id');
                $t->unsignedInteger('staff_id')->nullable();
                $t->unsignedInteger('institution_id')->nullable();
                $t->unsignedInteger('status_id')->nullable();
                $t->unsignedInteger('staff_leave_type_id')->nullable();
                $t->date('date_from')->nullable();
                $t->date('date_to')->nullable();
                $t->dateTime('created')->nullable();
            });
        }

        // institution_staff — needed by AlertStaffEmploymentCommand, AlertStaffTypeCommand, and AlertLicenseValidityCommand
        if (!Schema::hasTable('institution_staff')) {
            Schema::create('institution_staff', function (Blueprint $t) {
                $t->increments('id');
                $t->unsignedInteger('staff_id')->nullable();
                $t->unsignedInteger('institution_id')->nullable();
                $t->unsignedInteger('staff_type_id')->nullable();
                $t->unsignedInteger('staff_status_id')->nullable();
                $t->date('start_date')->nullable();
                $t->date('end_date')->nullable();
                $t->dateTime('created')->nullable();
            });
        }

        // staff_types — needed by AlertStaffTypeCommand
        if (!Schema::hasTable('staff_types')) {
            Schema::create('staff_types', function (Blueprint $t) {
                $t->increments('id');
                $t->string('name')->nullable();
                $t->string('code')->nullable();
                $t->dateTime('created')->nullable();
            });
        }

        // staff_statuses — needed by AlertStaffTypeCommand, AlertStaffEmploymentCommand, and AlertLicenseValidityCommand
        if (!Schema::hasTable('staff_statuses')) {
            Schema::create('staff_statuses', function (Blueprint $t) {
                $t->increments('id');
                $t->string('name')->nullable();
                $t->string('code')->nullable();
                $t->dateTime('created')->nullable();
            });
        }

        // employment_status_types — needed by AlertStaffEmploymentCommand
        if (!Schema::hasTable('employment_status_types')) {
            Schema::create('employment_status_types', function (Blueprint $t) {
                $t->increments('id');
                $t->string('name')->nullable();
                $t->dateTime('created')->nullable();
            });
        }

        // staff_employment_statuses — needed by AlertStaffEmploymentCommand
        if (!Schema::hasTable('staff_employment_statuses')) {
            Schema::create('staff_employment_statuses', function (Blueprint $t) {
                $t->increments('id');
                $t->unsignedInteger('staff_id')->nullable();
                $t->unsignedInteger('status_type_id')->nullable();
                $t->date('status_date')->nullable();
                $t->dateTime('created')->nullable();
            });
        }

        // staff_licenses — needed by AlertLicenseValidityCommand and AlertLicenseRenewalCommand
        if (!Schema::hasTable('staff_licenses')) {
            Schema::create('staff_licenses', function (Blueprint $t) {
                $t->increments('id');
                $t->unsignedInteger('security_user_id')->nullable();
                $t->unsignedInteger('license_type_id')->nullable();
                $t->string('license_number')->nullable();
                $t->date('issue_date')->nullable();
                $t->date('expiry_date')->nullable();
                $t->string('issuer')->nullable();
                $t->dateTime('created')->nullable();
            });
        }

        // license_types — needed by AlertLicenseValidityCommand and AlertLicenseRenewalCommand
        if (!Schema::hasTable('license_types')) {
            Schema::create('license_types', function (Blueprint $t) {
                $t->increments('id');
                $t->string('name')->nullable();
                $t->dateTime('created')->nullable();
            });
        }

        // scholarship_recipient_payment_structure_estimates — needed by AlertScholarshipDisbursementCommand
        if (!Schema::hasTable('scholarship_recipient_payment_structure_estimates')) {
            Schema::create('scholarship_recipient_payment_structure_estimates', function (Blueprint $t) {
                $t->increments('id');
                $t->unsignedInteger('scholarship_id')->nullable();
                $t->unsignedInteger('recipient_id')->nullable();
                $t->unsignedInteger('scholarship_disbursement_category_id')->nullable();
                $t->unsignedInteger('scholarship_recipient_payment_structure_id')->nullable();
                $t->date('estimated_disbursement_date')->nullable();
                $t->decimal('estimated_amount', 12, 2)->nullable();
                $t->text('comments')->nullable();
                $t->dateTime('created')->nullable();
            });
        }

        // scholarship_disbursement_categories — needed by AlertScholarshipDisbursementCommand
        if (!Schema::hasTable('scholarship_disbursement_categories')) {
            Schema::create('scholarship_disbursement_categories', function (Blueprint $t) {
                $t->increments('id');
                $t->string('name')->nullable();
                $t->dateTime('created')->nullable();
            });
        }

        // scholarship_recipient_payment_structures — needed by AlertScholarshipDisbursementCommand
        if (!Schema::hasTable('scholarship_recipient_payment_structures')) {
            Schema::create('scholarship_recipient_payment_structures', function (Blueprint $t) {
                $t->increments('id');
                $t->string('code')->nullable();
                $t->string('name')->nullable();
                $t->dateTime('created')->nullable();
            });
        }

        // scholarships — needed by AlertScholarshipDisbursementCommand
        if (!Schema::hasTable('scholarships')) {
            Schema::create('scholarships', function (Blueprint $t) {
                $t->increments('id');
                $t->string('code')->nullable();
                $t->string('name')->nullable();
                $t->text('description')->nullable();
                $t->date('application_open_date')->nullable();
                $t->date('application_close_date')->nullable();
                $t->decimal('maximum_award_amount', 12, 2)->nullable();
                $t->decimal('total_amount', 12, 2)->nullable();
                $t->string('duration')->nullable();
                $t->decimal('bond', 12, 2)->nullable();
                $t->dateTime('created')->nullable();
            });
        }

        // security_group_users — needed by various commands for institution filtering
        if (!Schema::hasTable('security_group_users')) {
            Schema::create('security_group_users', function (Blueprint $t) {
                $t->increments('id');
                $t->unsignedInteger('security_user_id')->nullable();
                $t->unsignedInteger('security_group_id')->nullable();
                $t->dateTime('created')->nullable();
            });
        }

        // security_group_institutions — needed by various commands for institution filtering
        if (!Schema::hasTable('security_group_institutions')) {
            Schema::create('security_group_institutions', function (Blueprint $t) {
                $t->increments('id');
                $t->unsignedInteger('security_group_id')->nullable();
                $t->unsignedInteger('institution_id')->nullable();
                $t->dateTime('created')->nullable();
            });
        }

        // scholarship_applications — needed by AlertScholarshipApplicationCommand
        if (!Schema::hasTable('scholarship_applications')) {
            Schema::create('scholarship_applications', function (Blueprint $t) {
                $t->increments('id');
                $t->unsignedInteger('scholarship_id')->nullable();
                $t->unsignedInteger('applicant_id')->nullable();
                $t->unsignedInteger('assignee_id')->nullable();
                $t->unsignedInteger('status_id')->nullable();
                $t->dateTime('created')->nullable();
            });
        }
    }
}
