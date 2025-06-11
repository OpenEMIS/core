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

        $this->backupTables();
        $this->removeSmsConfigItems();
        $this->insertConfigItems();
        $this->insertNewExternalDataSourceAttributes();
    }

    private function backupTables(): void
    {
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
        $this->backupTable('config_items', 'z_8286_config_items');
        $this->backupTable('external_data_source_attributes', 'z_8286_external_data_source_attributes');
        $this->execute('SET FOREIGN_KEY_CHECKS=1;');

    }

    private function backupTable(string $original, string $backup): void
    {
        if (!$this->hasTable($backup)) {
            $this->execute("CREATE TABLE `$backup` LIKE `$original`");

            // Check if the 'modified' column exists
            $table = $this->table($original);
            $columns = $table->getColumns($original);
            if (in_array('modified', $columns)) {
                // Update zero dates in the 'modified' column to NULL
                $this->execute("
            UPDATE `$original`
            SET `modified` = NULL
            WHERE `modified` = '0000-00-00 00:00:00'
        ");
            }

            // Insert data into the backup table
            $this->execute("INSERT INTO `$backup` SELECT * FROM `$original`");

            Log::info("Backed up `$original` to `$backup`");
        }

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
            ['number', 'Number', '+13472492183'],
//            ['twilio_api_url', 'API URL', 'https://api.twilio.com/2010-04-01/Accounts/'],
//            ['twilio_api_version', 'API Version', '2010-04-01'],
//            ['twilio_api_method', 'API Method', 'POST'],
//            ['twilio_api_timeout', 'API Timeout', '30'],
//            ['twilio_api_response_format', 'API Response Format', 'json'],
//            ['twilio_api_auth_type', 'API Auth Type', 'Basic'],

        ];

        $data = array_map(fn($attr) => $this->generateExternalDataSourceAttribute('Twilio', ...$attr), $attributes);

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
        $this->restoreTable('config_items', 'z_8286_config_items');
        $this->restoreTable('external_data_source_attributes', 'z_8286_external_data_source_attributes');
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
