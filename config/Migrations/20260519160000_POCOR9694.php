<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * POCOR-9694 — OpenEMIS Runtime + System Activities nav.
 *
 * Single migration for the whole ticket. Two related concerns ship
 * together because both are facets of the same architectural change:
 *
 * 1. Runtime / queue framework — three new tables (`tasks`,
 *    `task_jobs`, `task_failures`) that abstract over Laravel's
 *    queue and provide an OpenEMIS-native execution-tracking layer.
 *
 * 2. System Activities nav — the {{Administration → System Activities}}
 *    sidebar group that exposes the runtime to operators. Backed by
 *    six rows in `security_functions` (module=Administration,
 *    category='System Activities') gated to the super_admin role.
 *
 * Idempotent: re-running the migration is a no-op when the rows /
 * tables already exist.
 *
 * @see src/Controller/Component/NavigationComponent.php
 *      ::getAdministrationAsyncServicesNav()
 */
class POCOR9694 extends AbstractMigration
{
    private const TICKET = '9694';
    private const ADMINISTRATION_PARENT_ID = 5000;
    private const NAV_CATEGORY = 'System Activities';
    private const NAV_MODULE = 'Administration';

    /** Tables modified (rows added) by this migration — backed up in up(). */
    private const BACKUP_TABLES = [
        'security_functions',
        'security_role_functions',
    ];

    /**
     * Each row defines one System Activities nav entry.
     *
     * - {{name}} is the human-facing label rendered in the sidebar.
     * - {{controller}} is the CakePHP controller key used by the nav array.
     * - {{view_actions}} are the {{action}} segments granted by {{_view = 1}}
     *   on the matching role; concatenated with {{|}} to form the legacy
     *   {{_view}} text column.
     * - {{order_offset}} keeps the entries in display order; the absolute
     *   {{`order`}} value is computed as {{ORDER_BASE + offset}} so the whole
     *   block stays contiguous and easy to renumber.
     */
    private const NAV_ROWS = [
        [
            'name'         => 'Overview',
            'controller'   => 'Systems',
            'view_actions' => ['AsyncServices.index', 'AsyncServices.view'],
            'order_offset' => 0,
        ],
        [
            'name'         => 'Completed Jobs',
            'controller'   => 'Systems',
            'view_actions' => ['SystemProcesses.index', 'SystemProcesses.view'],
            'order_offset' => 1,
        ],
        [
            'name'         => 'Failed Jobs',
            'controller'   => 'Systems',
            'view_actions' => ['FailedJobs.index', 'FailedJobs.view', 'FailedJobsRetry.index'],
            'order_offset' => 2,
        ],
        [
            'name'         => 'Frozen Jobs',
            'controller'   => 'Systems',
            'view_actions' => ['StuckProcesses.index', 'StuckProcesses.view'],
            'order_offset' => 3,
        ],
        [
            'name'         => 'Failed Webhooks',
            'controller'   => 'Systems',
            'view_actions' => ['WebhookFailures.index', 'WebhookFailures.view'],
            'order_offset' => 4,
        ],
        [
            'name'         => 'Waiting Jobs',
            'controller'   => 'Systems',
            'view_actions' => ['QueueBacklog.index', 'QueueBacklog.view'],
            'order_offset' => 5,
        ],
    ];

    /** Order base — picked above the existing Communications block (ends at 295). */
    private const ORDER_BASE = 600;

    /** super_admin role id — the canonical sole grant for new admin sections. */
    private const SUPER_ADMIN_ROLE_ID = 10;

    public function up(): void
    {
        $this->backupTables();
        $this->createTasksTable();
        $this->createTaskJobsTable();
        $this->createTaskFailuresTable();
        $this->insertSecurityFunctions();
        $this->grantSuperAdmin();
    }

    /**
     * Reverse order of {{up()}}: revoke grants first, then drop nav rows,
     * then drop the runtime tables. Backup tables are intentionally NOT
     * restored — surgical deletes keep any concurrently-added rows safe.
     */
    public function down(): void
    {
        $this->revokeSuperAdmin();
        $this->deleteSecurityFunctions();
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
        $this->execute('DROP TABLE IF EXISTS `task_failures`');
        $this->execute('DROP TABLE IF EXISTS `task_jobs`');
        $this->execute('DROP TABLE IF EXISTS `tasks`');
        $this->execute('SET FOREIGN_KEY_CHECKS=1;');
    }

    // -------------------------------------------------------------------------
    // Backup
    // -------------------------------------------------------------------------

    private function backupTables(): void
    {
        foreach (self::BACKUP_TABLES as $table) {
            $backup = sprintf('z_%s_%s', self::TICKET, $table);
            if (!$this->hasTable($table) || $this->hasTable($backup)) {
                continue;
            }
            $this->execute('SET FOREIGN_KEY_CHECKS=0;');
            $this->execute("CREATE TABLE `$backup` LIKE `$table`");
            $this->execute("INSERT INTO `$backup` SELECT * FROM `$table`");
            $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    // -------------------------------------------------------------------------
    // tasks — main OpenEMIS Task queue (active + recent)
    // -------------------------------------------------------------------------

    private function createTasksTable(): void
    {
        if ($this->hasTable('tasks')) {
            return;
        }

        $this->table('tasks', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_general_ci',
            'comment' => 'POCOR-9694 OpenEMIS Tasks — abstraction over Laravel queue (shadow projection)',
        ])
            ->addColumn('id', 'biginteger', [
                'signed' => false,
                'identity' => true,
            ])
            ->addColumn('task_type', 'string', [
                'limit' => 64,
                'null' => false,
                'comment' => 'alert | webhook | export | profile | import | integration | event.<name>',
            ])
            ->addColumn('source_table', 'string', [
                'limit' => 64,
                'null' => true,
                'comment' => 'Legacy table this task mirrors (alert_queue, webhook_queue, jobs, …)',
            ])
            ->addColumn('source_id', 'biginteger', [
                'signed' => false,
                'null' => true,
                'comment' => 'Row id in source_table (FK-shape link, not enforced)',
            ])
            ->addColumn('payload_json', 'json', [
                'null' => true,
                'comment' => 'Structured payload — decoupled from Laravel job serialisation',
            ])
            ->addColumn('status', 'integer', [
                'default' => 0,
                'null' => false,
                'comment' => '0=NEW, 1=PROCESSING, 2=DONE, -1=ABORT, -2=FAILED',
            ])
            ->addColumn('available_at', 'datetime', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Do not process before this time',
            ])
            ->addColumn('started_at', 'datetime', [
                'null' => true,
            ])
            ->addColumn('completed_at', 'datetime', [
                'null' => true,
            ])
            ->addColumn('retry_count', 'integer', [
                'default' => 0,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
            ])
            ->addColumn('modified', 'datetime', [
                'null' => true,
                'update' => 'CURRENT_TIMESTAMP',
            ])
            ->addIndex(['status', 'available_at'], ['name' => 'idx_tasks_status_available'])
            ->addIndex(['task_type'], ['name' => 'idx_tasks_task_type'])
            ->addIndex(['source_table', 'source_id'], ['name' => 'idx_tasks_source'])
            ->create();
    }

    // -------------------------------------------------------------------------
    // task_jobs — per-attempt execution history
    // -------------------------------------------------------------------------

    private function createTaskJobsTable(): void
    {
        if ($this->hasTable('task_jobs')) {
            return;
        }

        $this->table('task_jobs', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_general_ci',
            'comment' => 'POCOR-9694 OpenEMIS Task execution attempts (one row per attempt)',
        ])
            ->addColumn('id', 'biginteger', [
                'signed' => false,
                'identity' => true,
            ])
            ->addColumn('task_id', 'biginteger', [
                'signed' => false,
                'null' => false,
                'comment' => 'FK-shape → tasks.id',
            ])
            ->addColumn('attempt_number', 'integer', [
                'default' => 1,
                'null' => false,
            ])
            ->addColumn('started_at', 'datetime', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
            ])
            ->addColumn('ended_at', 'datetime', [
                'null' => true,
            ])
            ->addColumn('duration_ms', 'integer', [
                'null' => true,
            ])
            ->addColumn('status', 'integer', [
                'default' => 1,
                'null' => false,
                'comment' => '1=PROCESSING, 2=DONE, -2=FAILED',
            ])
            ->addColumn('message_preview', 'string', [
                'limit' => 500,
                'null' => true,
                'comment' => 'Short outcome message for UI listing',
            ])
            ->addColumn('created', 'datetime', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
            ])
            ->addIndex(['task_id', 'attempt_number'], ['name' => 'idx_task_jobs_task_attempt'])
            ->addIndex(['status', 'started_at'], ['name' => 'idx_task_jobs_status_started'])
            ->create();
    }

    // -------------------------------------------------------------------------
    // task_failures — failure detail (lazy, only when status = -2)
    // -------------------------------------------------------------------------

    private function createTaskFailuresTable(): void
    {
        if ($this->hasTable('task_failures')) {
            return;
        }

        $this->table('task_failures', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_general_ci',
            'comment' => 'POCOR-9694 OpenEMIS Task failure detail (exception + stack)',
        ])
            ->addColumn('id', 'biginteger', [
                'signed' => false,
                'identity' => true,
            ])
            ->addColumn('task_id', 'biginteger', [
                'signed' => false,
                'null' => false,
                'comment' => 'FK-shape → tasks.id',
            ])
            ->addColumn('task_job_id', 'biginteger', [
                'signed' => false,
                'null' => true,
                'comment' => 'FK-shape → task_jobs.id (the failed attempt)',
            ])
            ->addColumn('exception_class', 'string', [
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('exception_message', 'text', [
                'null' => true,
            ])
            ->addColumn('stack_trace', 'text', [
                'null' => true,
            ])
            ->addColumn('retry_allowed', 'boolean', [
                'default' => true,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
            ])
            ->addIndex(['task_id', 'created'], ['name' => 'idx_task_failures_task_created'])
            ->create();
    }

    // -------------------------------------------------------------------------
    // System Activities nav — security_functions seed
    // -------------------------------------------------------------------------

    private function insertSecurityFunctions(): void
    {
        $this->withForeignKeyChecksOff(function (): void {
            foreach (self::NAV_ROWS as $row) {
                if ($this->securityFunctionExists($row['name'])) {
                    continue;
                }
                $this->execute(sprintf(
                    "INSERT INTO `security_functions`
                        (name, controller, module, category, parent_id, _view, `order`, visible, created_user_id, created)
                     VALUES
                        (%s, %s, %s, %s, %d, %s, %d, 1, 1, NOW())",
                    $this->quote($row['name']),
                    $this->quote($row['controller']),
                    $this->quote(self::NAV_MODULE),
                    $this->quote(self::NAV_CATEGORY),
                    self::ADMINISTRATION_PARENT_ID,
                    $this->quote(implode('|', $row['view_actions'])),
                    self::ORDER_BASE + (int)$row['order_offset']
                ));
            }
        });
    }

    private function deleteSecurityFunctions(): void
    {
        $this->withForeignKeyChecksOff(function (): void {
            $this->execute(sprintf(
                "DELETE FROM `security_functions`
                 WHERE module = %s AND category = %s",
                $this->quote(self::NAV_MODULE),
                $this->quote(self::NAV_CATEGORY)
            ));
        });
    }

    private function securityFunctionExists(string $name): bool
    {
        $stmt = $this->query(sprintf(
            "SELECT id FROM `security_functions`
             WHERE name = %s AND module = %s AND category = %s
             LIMIT 1",
            $this->quote($name),
            $this->quote(self::NAV_MODULE),
            $this->quote(self::NAV_CATEGORY)
        ));
        return $stmt->fetch() !== false;
    }

    // -------------------------------------------------------------------------
    // System Activities nav — security_role_functions grant for super_admin
    // -------------------------------------------------------------------------

    private function grantSuperAdmin(): void
    {
        $this->withForeignKeyChecksOff(function (): void {
            $this->execute(sprintf(
                "INSERT INTO `security_role_functions`
                    (security_role_id, security_function_id, _view, _edit, _add, _delete, _execute, created_user_id, created)
                 SELECT %d, sf.id, 1, 0, 0, 0, 0, 1, NOW()
                 FROM `security_functions` sf
                 LEFT JOIN `security_role_functions` srf
                     ON srf.security_function_id = sf.id
                    AND srf.security_role_id = %d
                 WHERE sf.module = %s
                   AND sf.category = %s
                   AND srf.security_function_id IS NULL",
                self::SUPER_ADMIN_ROLE_ID,
                self::SUPER_ADMIN_ROLE_ID,
                $this->quote(self::NAV_MODULE),
                $this->quote(self::NAV_CATEGORY)
            ));
        });
    }

    private function revokeSuperAdmin(): void
    {
        $this->withForeignKeyChecksOff(function (): void {
            $this->execute(sprintf(
                "DELETE srf FROM `security_role_functions` srf
                 JOIN `security_functions` sf ON sf.id = srf.security_function_id
                 WHERE srf.security_role_id = %d
                   AND sf.module = %s
                   AND sf.category = %s",
                self::SUPER_ADMIN_ROLE_ID,
                $this->quote(self::NAV_MODULE),
                $this->quote(self::NAV_CATEGORY)
            ));
        });
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Wraps a callable in `SET FOREIGN_KEY_CHECKS=0/1` so DML can run
     * without tripping referential integrity during seeding. The
     * `finally` clause guarantees re-enable even if the callable throws.
     */
    private function withForeignKeyChecksOff(callable $fn): void
    {
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
        try {
            $fn();
        } finally {
            $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    private function quote(string $value): string
    {
        return $this->getAdapter()->getConnection()->quote($value);
    }
}
