<?php
declare(strict_types=1);

use Migrations\AbstractMigration;
use Cake\Log\Log;
use Cake\Utility\Text;

class POCOR9303 extends AbstractMigration
{
    private const CONFIG_TYPE = 'PDF Service';
    private const OPTION_TYPE = 'pdf_printer_type';

    public function up(): void
    {
//        return;
        $this->backupTables();
        $this->insertConfigItemOptions();
        $this->insertConfigItems();
        $this->insertNewExternalDataSourceAttributes();
    }

    private function backupTables(): void
    {
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
        $this->backupTable('config_item_options', 'z_9303_config_item_options');
        $this->backupTable('config_items', 'z_9303_config_items');
        $this->backupTable('external_data_source_attributes', 'z_9303_external_data_source_attributes');
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


    private function insertConfigItems(): void
    {
        $items = [
            [
                'name' => 'PDF Service',
                'code' => 'pdf_service',
                'type' => self::CONFIG_TYPE,
                'label' => 'PDF Service',
                'visible' => 1,
                'editable' => 1,
                'value' => '1',
                'default_value' => '1',
                'value_selection' => '1',
                'field_type' => 'Dropdown',
                'option_type' => self::OPTION_TYPE,
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
                 '{$item['label']}', '{$item['value']}', '{$item['value_selection']}',
                 '{$item['default_value']}', {$item['editable']},
                 {$item['visible']}, $fieldType, $optionType, 1, CURRENT_TIMESTAMP)
            ");
                Log::info("Inserted config item: {$item['name']}");
            }
        }
    }private function insertConfigItemOptions(): void
    {
        //        $this->execute("INSERT INTO `config_item_options` (`option_type`, `option`, `value`, `order`, `visible`) values('meal_type','Received','Received','1','1')");
        //        $this->execute("INSERT INTO `config_item_options` (`option_type`, `option`, `value`, `order`, `visible`) values('meal_type','Not Received','Not Received','2','1')");
        //        $this->execute("INSERT INTO `config_item_options` (`option_type`, `option`, `value`, `order`, `visible`) values('meal_type','None','None','3','1')");
        $item_options = [
            [
                'option_type' => self::OPTION_TYPE,
                'option' => 'MPDF Printer',
                'value' => 1,
                'visible' => 1,
                'order' => 1,
            ],
            [
                'option_type' => self::OPTION_TYPE,
                'option' => 'LibreOffice Printer',
                'value' => 2,
                'visible' => 1,
                'order' => 2,
            ],
            [
                'option_type' => self::OPTION_TYPE,
                'option' => 'External PDF Printer',
                'value' => 3,
                'visible' => 1,
                'order' => 3,
            ],
        ];

        foreach ($item_options as $item_option) {
            $existing = $this->fetchRow("
            SELECT id FROM `config_item_options`
            WHERE `option_type` = '{$item_option['option_type']}'
              AND `option` = '{$item_option['option']}'
        ");

            if (empty($existing)) {

                $this->execute("
                INSERT INTO `config_item_options`
                (`option_type`, `option`, `value`, `visible`, `order`)
                VALUES
                ('{$item_option['option_type']}', '{$item_option['option']}',
                 {$item_option['value']},
                 '{$item_option['visible']}', {$item_option['order']})
            ");
                Log::info("Inserted config item option: {$item_option['name']}");
            }
        }
    }


    public function insertNewExternalDataSourceAttributes()
    {
        $table = $this->table('external_data_source_attributes');

        $attributes = [
            // API Credentials
            ['username', 'User Name', 'user'],
            ['password', 'Password', 'password'],
            ['api_url', 'API URL', 'http://pdf-printer:5000'],
            ['api_params', 'API Parameters', '{ "TiledWatermark":{"type":"string","value":"draft"},
            "EncryptFile": {"type": "boolean", "value": "true"},
            "DocumentOpenPassword": {"type": "string", "value": "secret"}}'],
        ];

        $data = array_map(fn($attr) => $this->generateExternalDataSourceAttribute('PDF Service', ...$attr), $attributes);

        $table->insert($data)->save();
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
        $this->restoreTable('config_item_options', 'z_9303_config_item_options');
        $this->restoreTable('config_items', 'z_9303_config_items');
        $this->restoreTable('external_data_source_attributes', 'z_9303_external_data_source_attributes');
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
