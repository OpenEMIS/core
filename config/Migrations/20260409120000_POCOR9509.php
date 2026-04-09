<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

//POCOR-9509: start - Consolidated migration merging POCOR7554 (alerts insert), POCOR9509 (alert_queue), and Laravel jobs/failed_jobs into a single CakePHP migration
class POCOR9509 extends AbstractMigration
{
    private const TICKET = '9509';

    // Tables that existed before this migration and need backup/restore
    private const BACKUP_TABLES = [
        'alerts',
        'alert_queue',
        'alert_logs', //POCOR-9509: backup needed for status column type change
    ];

    public function up(): void
    {
        $this->backupTables();
        $this->insertAlerts();        //POCOR-9509: POCOR7554 - add StudentStatus alert
        $this->createAlertQueue();   //POCOR-9509: alerts delivery queue
        $this->createJobsTable();     //POCOR-9509: Laravel queue jobs table
        $this->createFailedJobsTable(); //POCOR-9509: Laravel failed jobs table
        $this->migrateAlertLogsStatus(); //POCOR-9509: change alert_logs.status from VARCHAR to SMALLINT
    }

    public function down(): void
    {
        $this->restoreTables();
    }

    // -------------------------------------------------------------------------
    // Backup / Restore
    // -------------------------------------------------------------------------

    private function backupTables(): void
    {
        foreach (self::BACKUP_TABLES as $table) {
            if (!$this->hasTable($table)) {
                continue;
            }
            $backup = 'z_' . self::TICKET . '_' . $table;
            if ($this->hasTable($backup)) {
                continue;
            }
            $this->execute('SET FOREIGN_KEY_CHECKS=0;');
            $this->execute("CREATE TABLE `$backup` LIKE `$table`");
            $this->execute("INSERT INTO `$backup` SELECT * FROM `$table`");
            $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    private function restoreTables(): void
    {
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
        foreach (self::BACKUP_TABLES as $table) {
            $backup = 'z_' . self::TICKET . '_' . $table;
            if ($this->hasTable($backup)) {
                $this->execute("DROP TABLE IF EXISTS `$table`");
                $this->execute("RENAME TABLE `$backup` TO `$table`");
            } else {
                // No backup means table was created fresh — just drop it
                $this->execute("DROP TABLE IF EXISTS `$table`");
            }
        }
        // Drop new tables created by this migration (no backup needed)
        $this->execute('DROP TABLE IF EXISTS `jobs`');
        $this->execute('DROP TABLE IF EXISTS `failed_jobs`');
        $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        // alert_logs is restored from backup — column type reverts automatically
    }

    // -------------------------------------------------------------------------
    // POCOR7554: Insert StudentStatus alert
    // -------------------------------------------------------------------------

    private function insertAlerts(): void
    {
        if (!$this->hasTable('alerts')) {
            return;
        }
        $today = date('Y-m-d H:i:s');
        $this->execute("INSERT INTO `alerts`
            (`name`, `process_name`, `process_id`, `frequency`, `modified_user_id`, `modified`, `created_user_id`, `created`)
            VALUES
            ('StudentStatus', 'AlertStudentStatus', NULL, 'Once', NULL, '$today', 1, '$today')");
    }

    // -------------------------------------------------------------------------
    // POCOR9509: alert_queue
    // -------------------------------------------------------------------------

    private function createAlertQueue(): void
    {
        if ($this->hasTable('alert_queue')) {
            return;
        }

        $this->table('alert_queue', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_general_ci',
        ])
            ->addColumn('id', 'biginteger', [
                'signed' => false,
                'identity' => true,
            ])
            ->addColumn('alert_type', 'string', [
                'limit' => 100,
                'null' => false,
                'comment' => 'Logical alert type (Attendance, Workflow, License, etc)',
            ])
            ->addColumn('channel', 'string', [
                'limit' => 20,
                'null' => false,
                'comment' => 'email | sms | other',
            ])
            ->addColumn('recipient', 'string', [
                'limit' => 255,
                'null' => false,
                'comment' => 'Email address or phone number',
            ])
            ->addColumn('subject', 'string', [
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('message_body', 'text', [
                'null' => false,
            ])
            ->addColumn('payload', 'json', [
                'null' => true,
                'comment' => 'Optional structured payload for extensibility',
            ])
            ->addColumn('status', 'integer', [
                'default' => 0,
                'null' => false,
                'comment' => '0=pending,1=processing,2=sent,-1=failed',
            ])
            ->addColumn('retry_count', 'integer', [
                'default' => 0,
                'null' => false,
            ])
            ->addColumn('last_error', 'text', [
                'null' => true,
            ])
            ->addColumn('available_at', 'datetime', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Do not process before this time',
            ])
            ->addColumn('sent_at', 'datetime', [
                'null' => true,
            ])
            ->addColumn('created', 'datetime', [
                'default' => 'CURRENT_TIMESTAMP',
                'null' => false,
            ])
            ->addColumn('modified', 'datetime', [
                'null' => true,
            ])
            ->addIndex(['status', 'available_at'], ['name' => 'idx_status_available'])
            ->addIndex(['channel'], ['name' => 'idx_channel'])
            ->addIndex(['alert_type'], ['name' => 'idx_alert_type'])
            ->addIndex(['recipient'], ['name' => 'idx_recipient'])
            ->create();
    }

    // -------------------------------------------------------------------------
    // POCOR-9509: Change alert_logs.status from VARCHAR(20) to SMALLINT
    // Status values: -1=Failed, 0=Pending, 1=Success (language-agnostic integers)
    // -------------------------------------------------------------------------

    private function migrateAlertLogsStatus(): void
    {
        if (!$this->hasTable('alert_logs')) {
            return;
        }
        // Convert any existing string values to their integer equivalents before changing type
        $this->execute("UPDATE `alert_logs` SET `status` = -1 WHERE `status` = 'Failed'");
        $this->execute("UPDATE `alert_logs` SET `status` = 1 WHERE `status` = 'Success'");
        $this->execute("UPDATE `alert_logs` SET `status` = 0 WHERE `status` NOT IN ('-1', '1') OR `status` IS NULL");
        // Change column type: VARCHAR(20) -> SMALLINT NOT NULL DEFAULT 0
        // Comment preserved: -1=Failed, 0=Pending, 1=Success
        $this->execute("ALTER TABLE `alert_logs` MODIFY COLUMN `status` SMALLINT NOT NULL DEFAULT 0 COMMENT '-1=Failed, 0=Pending, 1=Success'");
    }

    // -------------------------------------------------------------------------
    // Laravel: jobs (queue worker table)
    // -------------------------------------------------------------------------

    private function createJobsTable(): void
    {
        if ($this->hasTable('jobs')) {
            return;
        }

        $this->execute("CREATE TABLE `jobs` (
            `id` bigint unsigned NOT NULL AUTO_INCREMENT,
            `queue` varchar(255) NOT NULL,
            `payload` longtext NOT NULL,
            `attempts` tinyint unsigned NOT NULL,
            `reserved_at` int unsigned DEFAULT NULL,
            `available_at` int unsigned NOT NULL,
            `created_at` int unsigned NOT NULL,
            PRIMARY KEY (`id`),
            KEY `jobs_queue_index` (`queue`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    // -------------------------------------------------------------------------
    // Laravel: failed_jobs
    // -------------------------------------------------------------------------

    private function createFailedJobsTable(): void
    {
        if ($this->hasTable('failed_jobs')) {
            return;
        }

        $this->execute("CREATE TABLE `failed_jobs` (
            `id` bigint unsigned NOT NULL AUTO_INCREMENT,
            `uuid` varchar(255) NOT NULL,
            `connection` text NOT NULL,
            `queue` text NOT NULL,
            `payload` longtext NOT NULL,
            `exception` longtext NOT NULL,
            `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
}
//POCOR-9509: end
