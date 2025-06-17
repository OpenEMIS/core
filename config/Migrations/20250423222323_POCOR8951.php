<?php
declare(strict_types=1);

use Migrations\AbstractMigration;
use Cake\Log\Log;

class POCOR8951 extends AbstractMigration
{
    private const CONFIG_TYPE = 'Online Services';
    private const OPTION_TYPE = 'online_services';
    private const ITEM_REGISTRATION = 'openemis_registration';
    private const ITEM_CORE = 'openemis_core';

    public function up(): void
    {
        $this->backupTables();
        $this->insertConfigItems();
        $this->insertConfigItemOptions();
        $this->modifyThemesTable();
    }

    private function backupTables(): void
    {
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
        $this->backupTable('config_items', 'z_8951_config_items');
        $this->backupTable('config_item_options', 'z_8951_config_item_options');
        $this->backupTable('themes', 'z_8951_themes');
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

    private function insertConfigItems(): void
    {
        $items = [
            [
                'name' => 'OpenEMIS Registration',
                'code' => self::ITEM_REGISTRATION,
                'visible' => 1,
                'editable' => 0
            ],
            [
                'name' => 'OpenEMIS Core',
                'code' => self::ITEM_CORE,
                'visible' => 0,
                'editable' => 0
            ]
        ];

        foreach ($items as $item) {
            $existing = $this->fetchRow("
                SELECT id FROM `config_items`
                WHERE `name` = '{$item['name']}'
                  AND `code` = '{$item['code']}'
                  AND `type` = '" . self::CONFIG_TYPE . "'
            ");

            if (empty($existing)) {
                $this->execute("
                    INSERT INTO `config_items`
                    (`name`, `code`, `type`, `label`, `value`, `value_selection`,
                     `default_value`, `editable`, `visible`, `field_type`,
                     `option_type`, `created_user_id`, `created`)
                    VALUES
                    ('{$item['name']}', '{$item['code']}', '" . self::CONFIG_TYPE . "',
                     '{$item['name']}', '1', '', '1', {$item['editable']},
                     {$item['visible']}, 'Dropdown', '" . self::OPTION_TYPE . "', 1, CURRENT_TIMESTAMP)
                ");
                Log::info("Inserted config item: {$item['name']}");
            }
        }
    }

    private function insertConfigItemOptions(): void
    {
        $options = [
            ['option' => 'Enabled', 'value' => '1'],
            ['option' => 'Disabled', 'value' => '0']
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

    private function modifyThemesTable(): void
    {
        $themesTable = $this->table('themes');

        if (!$themesTable->hasColumn('config_item_id')) {
            $this->execute("ALTER TABLE `themes` ADD COLUMN `config_item_id` INT AFTER `id`");
            $this->execute("ALTER TABLE `themes` ADD INDEX (`config_item_id`)");
            Log::info("Added `config_item_id` column to `themes` table");
        }

        $coreItem = $this->fetchRow("
            SELECT id FROM `config_items`
            WHERE `code` = '" . self::ITEM_CORE . "'
              AND `type` = '" . self::CONFIG_TYPE . "'
        ");

        if (!empty($coreItem)) {
            $this->execute("UPDATE `themes` SET `config_item_id` = {$coreItem['id']}");
            Log::info("Updated all existing `themes` to use `OpenEMIS Core`");
        }

        if (!$themesTable->hasForeignKey('config_item_id')) {
            $this->execute("
                ALTER TABLE `themes`
                ADD CONSTRAINT `fk_themes_config_item`
                FOREIGN KEY (`config_item_id`)
                REFERENCES `config_items`(`id`)
            ");
            Log::info("Foreign key added on `themes.config_item_id`");
        }

        $registrationItem = $this->fetchRow("
            SELECT id FROM `config_items`
            WHERE `code` = '" . self::ITEM_REGISTRATION . "'
              AND `type` = '" . self::CONFIG_TYPE . "'
        ");

        if (!empty($registrationItem)) {
            $themes = $this->fetchAll("SELECT * FROM `themes`");
            $now = date('Y-m-d H:i:s');
            $count = 0;

            foreach ($themes as &$theme) {
                $theme = array_filter($theme, fn($k) => !is_int($k), ARRAY_FILTER_USE_KEY);
                unset($theme['id']);

                $theme['config_item_id'] = $registrationItem['id'];
                $theme['created'] = $now;

                $columns = implode(", ", array_keys($theme));
                $values = implode(', ', array_map(function ($v) {
                    if (is_null($v)) {
                        return 'NULL';
                    }
                    return is_string($v) ? "'" . addslashes($v) . "'" : $v;
                }, $theme));

                $this->execute("INSERT INTO `themes` ($columns) VALUES ($values)");
                $count++;
            }

            Log::info("Duplicated {$count} themes for `OpenEMIS Registration`");
        }
    }

    public function down(): void
    {
        $this->restoreTables();
    }

    private function restoreTables(): void
    {
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');

        $this->restoreTable('config_items', 'z_8951_config_items');
        $this->restoreTable('config_item_options', 'z_8951_config_item_options');

        if ($this->table('themes')->hasForeignKey('config_item_id')) {
            $this->execute("ALTER TABLE `themes` DROP FOREIGN KEY `fk_themes_config_item`");
        }

        $this->restoreTable('themes', 'z_8951_themes');
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
