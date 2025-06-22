<?php
declare(strict_types=1);

use Migrations\AbstractMigration;
use Cake\Log\Log;
use Cake\Utility\Text;

class POCOR8286 extends AbstractMigration
{
    private const CONFIG_TYPE = 'External Alert Service - SMS';

    public function up(): void
    {
//        return;
        $this->backupTables();
        $this->changeInAlertsTable();
        $this->removeSmsConfigItems();
        $this->insertConfigItems();
        $this->insertNewExternalDataSourceAttributes();
        $this->addMethodField();
    }

    private function backupTables(): void
    {
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
        $this->backupTable('alerts', 'z_8286_alerts');
        $this->backupTable('config_items', 'z_8286_config_items');
        $this->backupTable('external_data_source_attributes', 'z_8286_external_data_source_attributes');
        $this->backupTable('messaging', 'z_8286_messaging');
        $this->execute('SET FOREIGN_KEY_CHECKS=1;');

    }

    private function backupTable(string $original, string $backup): void
    {
        if ($this->hasTable($backup)) {
            Log::warning("Backup table `$backup` already exists. Skipping backup.");
            return;
        }

        try {
            // 1. Create backup structure
            $this->execute("CREATE TABLE `$backup` LIKE `$original`");

            // 2. Get column names
            $table = $this->table($original);
            $columns = $table->getColumns();
            $columnNames = array_map(fn($col) => $col->getName(), $columns);

            Log::info('Table columns: ' . print_r($columnNames, true));

            // 3. Normalize 'modified' if exists
            if (in_array('modified', $columnNames)) {
                // Backup current SQL mode
                $sqlModeRow = $this->fetchRow("SELECT @@SESSION.sql_mode AS sql_mode");
                $currentSqlMode = $sqlModeRow['sql_mode'] ?? '';
                $safeSqlMode = addslashes($currentSqlMode);

                // Temporarily disable SQL mode
                $this->execute("SET SESSION sql_mode = ''");

                try {
                    $this->execute("
                    UPDATE `$original`
                    SET `modified` = NULL
                    WHERE `modified` = '0000-00-00 00:00:00'
                ");
                    Log::info("Successfully changed '0000-00-00 00:00:00' to NULL in `$original`");
                } finally {
                    // Restore original SQL mode
                    $this->execute("SET SESSION sql_mode = '$safeSqlMode'");
                }
            }

            // 4. Copy data into backup
            $this->execute("INSERT INTO `$backup` SELECT * FROM `$original`");

            Log::info("Successfully backed up `$original` to `$backup`");

        } catch (\Throwable $e) {
            // Cleanup failed backup table
            if ($this->hasTable($backup)) {
                $this->execute("DROP TABLE `$backup`");
                Log::warning("Backup failed; dropped incomplete backup table `$backup`");
            }

            // Re-throw to stop migration
            throw $e;
        }
    }


    private function changeInAlertsTable(): void
    {
        // 1. Rename 'Student Attendance' to 'StudentAttendance'
        $this->execute("UPDATE `alerts` SET `name` = 'StudentAttendance' WHERE `name` = 'Student Attendance'");
        Log::info("Renamed 'Student Attendance' to 'StudentAttendance'");

        // 2. Insert new alert for 'StudentEnrolment'
        $this->execute("
        INSERT INTO alerts (
            id, name, process_name, process_id, frequency,
            modified_user_id, modified, created_user_id, created
        ) VALUES (
            NULL, 'StudentEnrolment', 'AlertStudentEnrolment', NULL, 'Never',
            NULL, NULL, 1, NOW()
        )
    ");
        Log::info("Inserted new alert for 'StudentEnrolment'");

        // 3. Change frequency to 'Once' for specific alerts
        $this->execute("
        UPDATE alerts
        SET frequency = 'Once'
        WHERE frequency NOT IN ('Never', 'Once')
          AND process_name IN ('AlertAttendance', 'AlertStudentAdmission')
    ");
        Log::info("Updated frequency to 'Once' for alerts with process_name in ('AlertAttendance', 'AlertStudentAdmission')");
    }

    private function removeSmsConfigItems(): void
    {
        $this->execute("DELETE FROM `config_items` WHERE `type` = 'SMS'");
        Log::info("Removed all config items with type 'SMS'");
    }

    private function insertConfigItems(): void
    {
        // 1345,
        //Twilio,external_alert_service_sms_twilio,External Alert Service - SMS,Twilio,1,"",1,1,1,Dropdown,online_services,2,2025-05-02 08:13:24,1,2025-05-02 00:09:39
        $items = [
            [
                'name' => 'Twilio',
                'code' => 'external_alert_service_sms_twilio',
                'type' => self::CONFIG_TYPE,
                'label' => 'Twilio',
                'visible' => 1,
                'editable' => 1,
                'field_type' => 'Dropdown',
                'option_type' => 'online_services',
            ],
        ];

        foreach ($items as $item) {
            $existing = $this->fetchRow("
            SELECT id FROM `config_items`
            WHERE `name` = '{$item['name']}'
              AND `code` = '{$item['code']}'
              AND `type` = '" . self::CONFIG_TYPE . "'
        ");

            if (empty($existing)) {
                $fieldType = isset($item['field_type']) ? "'{$item['field_type']}'" : "''";
                $optionType = isset($item['option_type']) ? "'{$item['option_type']}'" : "''";

                $this->execute("
                INSERT INTO `config_items`
                (`name`, `code`, `type`, `label`, `value`, `value_selection`,
                 `default_value`, `editable`, `visible`, `field_type`,
                 `option_type`, `created_user_id`, `created`)
                VALUES
                ('{$item['name']}', '{$item['code']}', '" . self::CONFIG_TYPE . "',
                 '{$item['label']}', '0', '0', '0', {$item['editable']},
                 {$item['visible']}, $fieldType, $optionType, 1, CURRENT_TIMESTAMP)
            ");
                Log::info("Inserted config item: {$item['name']}");
            }
        }
    }


    public function insertNewExternalDataSourceAttributes()
    {
        $table = $this->table('external_data_source_attributes');

        $attributes = [
            // API Credentials
            ['account_sid', 'Account SID', 'openemis'],
            ['auth_token', 'Auth Token', 'YWRtaW46ZGVtbwjhfh'],
            ['twilio_number', 'Number', '+13472492183']

        ];

        $data = array_map(fn($attr) => $this->generateExternalDataSourceAttribute('Twilio', ...$attr), $attributes);

        $table->insert($data)->save();
    }
    private function addMethodField(): void
    {
        $table = $this->table('messaging');
        $columns = $table->getColumns();
        $columnNames = array_map(fn($col) => $col->getName(), $columns);

        Log::info('Current columns in `messaging`: ' . implode(', ', $columnNames));

        if (!in_array('method', $columnNames)) {
            $this->execute("ALTER TABLE `messaging` ADD `method` VARCHAR(50) NOT NULL DEFAULT 'Email' AFTER `status`;");
            Log::info("Added 'method' field to 'messaging' table");
        } else {
            Log::info("'method' field already exists in 'messaging' table – skipping");
        }
    }
    private function generateExternalDataSourceAttribute($type, $field, $name, $value)
    {
        return [
            'id' => Text::uuid(),
            'external_data_source_type' => $type,
            'attribute_field' => $field,
            'attribute_name' => $name,
            'value' => $value,
            'created' => date('Y-m-d H:i:s'),
            'created_user_id' => 1,
        ];
    }


    public function down(): void
    {

        $this->restoreTables();
    }

    private function restoreTables(): void
    {
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
        $this->restoreTable('alerts', 'z_8286_alerts');
        $this->restoreTable('config_items', 'z_8286_config_items');
        $this->restoreTable('external_data_source_attributes', 'z_8286_external_data_source_attributes');
        $this->restoreTable('messaging', 'z_8286_messaging');
        $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        Log::info("Restored tables from backups");
    }

    private function restoreTable(string $original, string $backup): void
    {
        if ($this->hasTable($backup)) {
            $this->execute("DROP TABLE IF EXISTS `$original`");
            $this->execute("RENAME TABLE `$backup` TO `$original`");
            $this->execute("DROP TABLE IF EXISTS `$backup`");
            Log::info("Restored `$original` from `$backup`");
        }
    }
}
