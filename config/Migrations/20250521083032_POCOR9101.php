<?php
declare(strict_types=1);

use Migrations\AbstractMigration;
use Cake\Log\Log;

class POCOR9101 extends AbstractMigration
{

    public function up(): void
    {
        $this->backupTables();
        $this->removeObsoleteConfigItems(); // ⬅️ added
        $this->insertConfigItems();
    }

    private function backupTables(): void
    {
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
        $this->backupTable('config_items', 'z_9101_config_items');
        $this->execute('SET FOREIGN_KEY_CHECKS=1;');
    }

    private function removeObsoleteConfigItems(): void
    {
        $this->execute("
        DELETE FROM `config_items`
        WHERE
            (`name` = 'Contacts' AND `code` = 'StudentContacts' AND `type` = 'Add New Student')
            OR
            (`name` = 'Contacts' AND `code` = 'StaffContacts' AND `type` = 'Add New Staff')
    ");
        Log::info("Removed obsolete Contacts config items");
    }

    private function backupTable(string $original, string $backup): void
    {
        if (!$this->hasTable($backup)) {
            $this->execute("CREATE TABLE `$backup` LIKE `$original`");
            $this->execute("INSERT INTO `$backup` SELECT * FROM `$original`");
            Log::info("Backed up `$original` to `$backup`");
        }
    }

    private function insertConfigItems(): void
    {
        $items = [
            [
                'name' => 'Email',
                'code' => 'student_email',
                'type' => 'Add New Student',
                'label' => 'Email',
                'value' => '0',
            ],
            [
                'name' => 'Email',
                'code' => 'staff_email',
                'type' => 'Add New Staff',
                'label' => 'Email',
                'value' => '0',
            ],
            [
                'name' => 'Mobile',
                'code' => 'student_mobile',
                'type' => 'Add New Student',
                'label' => 'Mobile',
                'value' => '0',
            ],
            [
                'name' => 'Mobile',
                'code' => 'staff_mobile',
                'type' => 'Add New Staff',
                'label' => 'Mobile',
                'value' => '0',
            ],
        ];

        foreach ($items as $item) {
            $existing = $this->fetchRow("
        SELECT id FROM `config_items`
        WHERE `name` = '{$item['name']}'
          AND `code` = '{$item['code']}'
          AND `type` = '{$item['type']}'
    ");

            if (empty($existing)) {
                $label = $item['label'] ?? $item['name'];
                $value = $item['value'] ?? '1';
                $editable = $item['editable'] ?? 1;
                $visible = $item['visible'] ?? 1;
                $fieldType = 'Dropdown';
                $optionType = 'wizard';

                $this->execute("
            INSERT INTO `config_items`
            (`name`, `code`, `type`, `label`, `value`, `value_selection`,
             `default_value`, `editable`, `visible`, `field_type`,
             `option_type`, `created_user_id`, `created`)
            VALUES
            ('{$item['name']}', '{$item['code']}', '{$item['type']}',
             '{$label}', '{$value}', '', '0', {$editable},
             {$visible}, '{$fieldType}', '{$optionType}', 1, CURRENT_TIMESTAMP)
        ");
                Log::info("Inserted config item: {$item['name']} / {$item['code']}");
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

        $this->restoreTable('config_items', 'z_9101_config_items');
        $this->restoreTable('config_item_options', 'z_9101_config_item_options');

        if ($this->table('themes')->hasForeignKey('config_item_id')) {
            $this->execute("ALTER TABLE `themes` DROP FOREIGN KEY `fk_themes_config_item`");
        }

        $this->restoreTable('themes', 'z_9101_themes');
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
