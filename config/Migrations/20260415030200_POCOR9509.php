<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

//POCOR-9509
class POCOR9509 extends AbstractMigration
{
    private const TICKET = '9509';

    private const BACKUP_TABLES = [
        'alerts',
        'alert_queue',
        'alert_logs',
    ];

    public function up(): void
    {
        $this->backupTables();
        $this->addAlertsUniqueIndexes(); //POCOR-9509
        $this->insertAlerts();
        $this->createAlertQueue();
        $this->createJobsTable();
        $this->createFailedJobsTable();
        $this->migrateAlertLogsStatus();
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
                $this->execute("DROP TABLE IF EXISTS `$table`");
            }
        }
        $this->execute('DROP TABLE IF EXISTS `jobs`');
        $this->execute('DROP TABLE IF EXISTS `failed_jobs`');
        $this->execute('SET FOREIGN_KEY_CHECKS=1;');
    }

    private function addAlertsUniqueIndexes(): void //POCOR-9509
    {
        if (!$this->hasTable('alerts')) {
            return;
        }
        // Remove duplicate name rows — keep lowest id per name
        $this->execute(
            "DELETE a FROM `alerts` a
             INNER JOIN `alerts` b ON a.name = b.name AND a.id > b.id"
        );
        // Remove duplicate process_name rows — keep lowest id per process_name
        $this->execute(
            "DELETE a FROM `alerts` a
             INNER JOIN `alerts` b ON a.process_name = b.process_name AND a.id > b.id"
        );
        $db = $this->getAdapter()->getOption('name');
        $nameExists = $this->fetchRow(
            "SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS
             WHERE TABLE_SCHEMA = '$db' AND TABLE_NAME = 'alerts' AND INDEX_NAME = 'alerts_name_unique'"
        );
        if (!$nameExists) {
            $this->execute("ALTER TABLE `alerts` ADD UNIQUE INDEX `alerts_name_unique` (`name`)");
        }
        $processExists = $this->fetchRow(
            "SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS
             WHERE TABLE_SCHEMA = '$db' AND TABLE_NAME = 'alerts' AND INDEX_NAME = 'alerts_process_name_unique'"
        );
        if (!$processExists) {
            $this->execute("ALTER TABLE `alerts` ADD UNIQUE INDEX `alerts_process_name_unique` (`process_name`)");
        }
    }

    private function insertAlerts(): void //POCOR-9509
    {
        if (!$this->hasTable('alerts')) {
            return;
        }
        $today = date('Y-m-d H:i:s');
        $this->execute("INSERT IGNORE INTO `alerts`
            (`name`, `process_name`, `process_id`, `frequency`, `modified_user_id`, `modified`, `created_user_id`, `created`)
            VALUES
            ('StudentStatus', 'AlertStudentStatus', NULL, 'Once', NULL, '$today', 1, '$today')"); //POCOR-9509
        $this->execute("INSERT IGNORE INTO `alerts`
            (`name`, `process_name`, `process_id`, `frequency`, `modified_user_id`, `modified`, `created_user_id`, `created`)
            VALUES
            ('RetirementWarning', 'AlertRetirementWarning', NULL, 'Never', NULL, '$today', 1, '$today')"); //POCOR-9509
    }

    private function createAlertQueue(): void //POCOR-9509
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

    private function migrateAlertLogsStatus(): void //POCOR-9509
    {
        if (!$this->hasTable('alert_logs')) {
            return;
        }
        $this->execute("UPDATE `alert_logs` SET `status` = -1 WHERE `status` = 'Failed'");
        $this->execute("UPDATE `alert_logs` SET `status` = 1 WHERE `status` = 'Success'");
        $this->execute("UPDATE `alert_logs` SET `status` = 0 WHERE `status` NOT IN ('-1', '1') OR `status` IS NULL");
        $this->execute("ALTER TABLE `alert_logs` MODIFY COLUMN `status` SMALLINT NOT NULL DEFAULT 0 COMMENT '-1=Failed, 0=Pending, 1=Success'");
    }

    private function createJobsTable(): void //POCOR-9509
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

    private function createFailedJobsTable(): void //POCOR-9509
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
