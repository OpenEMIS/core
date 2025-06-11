<?php
declare(strict_types=1);

use Migrations\AbstractMigration;
use Cake\Log\Log;

class POCOR8286 extends AbstractMigration
{
    private const CONFIG_TYPE = 'External Alert Service - SMS';
    private const OPTION_TYPE = 'external_alert_service_sms';
    private const ITEM_SMS = 'external_alert_service_sms_config';

    public function up(): void
    {

        $this->backupTables();
        $this->removeSmsConfigItems();
        $this->insertConfigItems();
        $this->insertConfigItemOptions();
    }

    private function backupTables(): void
    {
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
        $this->backupTable('config_items', 'z_8286_config_items');
        $this->backupTable('config_item_options', 'z_8286_config_item_options');
        $this->execute('SET FOREIGN_KEY_CHECKS=1;');
    }

    private function backupTable(string $original, string $backup): void
    {
        if (!$this->hasTable($backup)) {
            $this->execute("CREATE TABLE `$backup` LIKE `$original`");
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
        $items = [
            [
                'name' => 'SMS Provider',
                'code' => self::ITEM_SMS,
                'visible' => 1,
                'editable' => 1,
                'field_type' => 'Dropdown',
                'option_type' => self::OPTION_TYPE,
            ],
//            [
//                'name' => 'Status',
//                'code' => 'sms_status',
//                'visible' => 1,
//                'editable' => 1,
//                'field_type' => 'Dropdown',
//                'option_type' => 'online_services',
//            ],
//            [
//                'name' => 'Account SID',
//                'code' => 'sms_account_sid',
//                'visible' => 1,
//                'editable' => 1
//            ],
//            [
//                'name' => 'SMS Auth Token',
//                'code' => 'sms_auth_token',
//                'visible' => 1,
//                'editable' => 1
//            ],
//            [
//                'name' => 'SMS Number',
//                'code' => 'sms_number',
//                'visible' => 1,
//                'editable' => 1
//            ]
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
                 '{$item['name']}', '', '', '1', {$item['editable']},
                 {$item['visible']}, $fieldType, $optionType, 1, CURRENT_TIMESTAMP)
            ");
                Log::info("Inserted config item: {$item['name']}");
            }
        }
    }


    private function insertConfigItemOptions(): void
    {
        $options = [
            ['option' => 'Twilio', 'value' => 'Twilio'],
            ['option' => 'Nexmo (Vonage API)', 'value' => 'Nexmo'],
            ['option' => 'Plivo', 'value' => 'Plivo'],
            ['option' => 'MessageBird', 'value' => 'MessageBird'],
            ['option' => 'Sinch', 'value' => 'Sinch'],
            ['option' => 'Clickatell', 'value' => 'Clickatell'],
            ['option' => 'Textlocal', 'value' => 'Textlocal'],
            ['option' => 'Zenvia', 'value' => 'Zenvia'],
            ['option' => 'Infobip', 'value' => 'Infobip'],
            ['option' => 'Telesign', 'value' => 'Telesign'],
            ['option' => 'Bandwidth', 'value' => 'Bandwidth'],
            ['option' => 'SMSGlobal', 'value' => 'SMSGlobal'],
        ];

        foreach ($options as $data) {
            $existing = $this->fetchRow("
            SELECT id FROM `config_item_options`
            WHERE `option_type` = '" . self::OPTION_TYPE . "'
              AND `option` = '{$data['option']}'
        ");

            if (empty($existing)) {
                $this->execute("
                INSERT INTO `config_item_options`
                (`option_type`, `option`, `value`, `order`, `visible`)
                VALUES ('" . self::OPTION_TYPE . "', '{$data['option']}', '{$data['value']}', 0, 1)
            ");
                Log::info("Inserted option: {$data['option']}");
            }
        }
    }


    public function down(): void
    {

        $this->restoreTables();
    }

    private function restoreTables(): void
    {
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
        $this->restoreTable('config_items', 'z_8286_config_items');
        $this->restoreTable('config_item_options', 'z_8286_config_item_options');
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
