<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

//POCOR-9694
class POCOR9694 extends AbstractMigration
{
    private const TICKET = '9694';

    //POCOR-9694: only NEW tables — no backup required
    public function up(): void
    {
        $this->createTasksTable();         //POCOR-9694
        $this->createTaskJobsTable();      //POCOR-9694
        $this->createTaskFailuresTable();  //POCOR-9694
    }

    public function down(): void
    {
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
        $this->execute('DROP TABLE IF EXISTS `task_failures`');
        $this->execute('DROP TABLE IF EXISTS `task_jobs`');
        $this->execute('DROP TABLE IF EXISTS `tasks`');
        $this->execute('SET FOREIGN_KEY_CHECKS=1;');
    }

    //POCOR-9694: tasks — main OpenEMIS Task queue (active + recent)
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

    //POCOR-9694: task_jobs — per-attempt execution history
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

    //POCOR-9694: task_failures — failure detail (lazy, only when status=-2)
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
}
