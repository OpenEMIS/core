<?php
declare(strict_types=1);

use Migrations\AbstractMigration;
use Cake\Log\Log;

class POCOR8951 extends AbstractMigration
{
    public function up()
    {
        $this->backupTables();
        $this->insertConfigItems();
        $this->insertConfigItemOptions();
        $this->modifyThemesTable();
    }

    /**
     * Backup the existing tables.
     *
     * @return void
     */
    private function backupTables(): void
    {
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
        $this->backupTable('config_items', 'z_8951_config_items');
        $this->backupTable('config_item_options', 'z_8951_config_item_options');
        $this->backupTable('themes', 'z_8951_themes'); // Backup themes table
        $this->execute('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Backup a single table.
     *
     * @param string $originalTable The name of the original table.
     * @param string $backupTable The name of the backup table.
     * @return void
     */
    private function backupTable(string $originalTable, string $backupTable): void
    {
        if (!$this->hasTable($backupTable)) {
            $this->execute("CREATE TABLE `$backupTable` LIKE `$originalTable`");
            $this->execute("INSERT IGNORE INTO `$backupTable` SELECT * FROM `$originalTable`");
        }
    }

    /**
     * Insert new configuration items if they do not already exist.
     *
     * @return void
     */
    private function insertConfigItems(): void
    {
        // Check if the record already exists
        $existingRecord = $this->fetchRow("
        SELECT * FROM `config_items`
        WHERE `name` = 'OpenEMIS Registration'
          AND `code` = 'openemis_registration'
          AND `type` = 'Online Services'
    ");

        if (empty($existingRecord)) {
            $this->execute("
            INSERT IGNORE INTO `config_items`
            (`id`, `name`, `code`, `type`, `label`,
                   `value`, `value_selection`, `default_value`, `editable`, `visible`,
                   `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`)
            VALUES
            (NULL, 'OpenEMIS Registration', 'openemis_registration', 'Online Services', 'OpenEMIS Registration',
                   '1', '', '1', 0, 1,
                   'Dropdown', 'online_services', NULL, NULL, 1, CURRENT_TIMESTAMP)
        ");
        }

        $existingRecord = $this->fetchRow("
        SELECT * FROM `config_items`
        WHERE `name` = 'OpenEMIS Core'
          AND `code` = 'openemis_core'
          AND `type` = 'Online Services'
    ");

        if (empty($existingRecord)) {
            $this->execute("
            INSERT IGNORE INTO `config_items`
            (`id`, `name`, `code`, `type`, `label`,
                   `value`, `value_selection`, `default_value`, `editable`, `visible`,
                   `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`)
            VALUES
            (NULL, 'OpenEMIS Core', 'openemis_core', 'Online Services', 'OpenEMIS Core',
                   '1', '', '1', 0, 0,
                   'Dropdown', 'online_services', NULL, NULL, 1, CURRENT_TIMESTAMP)
        ");
        }
    }

    /**
     * Insert new configuration item options if they do not already exist.
     *
     * @return void
     */
    private function insertConfigItemOptions(): void
    {
        // Define the options to be inserted
        $options = [
            ['option' => 'Enabled', 'value' => '1'],
            ['option' => 'Disabled', 'value' => '0']
        ];

        foreach ($options as $optionData) {
            // Check if the record already exists
            $existingRecord = $this->fetchRow("
            SELECT * FROM `config_item_options`
            WHERE `option_type` = 'online_services'
              AND `option` = '{$optionData['option']}'
        ");

            if (empty($existingRecord)) {
                $this->execute("
                INSERT INTO `config_item_options` (`id`, `option_type`, `option`, `value`, `order`, `visible`)
                VALUES
                (NULL, 'online_services', '{$optionData['option']}', '{$optionData['value']}', '0', '1')
            ");
            }
        }
    }

    /**
     * Modify the themes table to add config_item_id and set default value.
     *
     * @return void
     */
    private function modifyThemesTable(): void
    {
        // Check if the config_item_id column already exists
        if (!$this->table('themes')->hasColumn('config_item_id')) {
            // Add config_item_id column
            $this->execute("ALTER TABLE `themes` ADD COLUMN `config_item_id` INT AFTER `id`");

            // Retrieve the ID of 'OpenEMIS Core'
            $openemisCoreId = $this->fetchRow("
        SELECT `id` FROM `config_items`
        WHERE `code` = 'openemis_core'
          AND `type` = 'Online Services'
    ");

            if (!empty($openemisCoreId)) {
                // Set default value for existing records
                $this->execute("
            UPDATE `themes` SET `config_item_id` = {$openemisCoreId['id']}
        ");
            }
        }

        // Check if the foreign key already exists
        if (!$this->table('themes')->hasForeignKey('config_item_id')) {
            // Add foreign key constraint
            $this->execute("
        ALTER TABLE `themes`
        ADD CONSTRAINT `fk_themes_config_item`
        FOREIGN KEY (`config_item_id`) REFERENCES `config_items`(`id`)
    ");
        }

        // Retrieve the ID of 'OpenEMIS Registration'
        $openemisRegistrationId = $this->fetchRow("
    SELECT `id` FROM `config_items`
    WHERE `code` = 'openemis_registration'
      AND `type` = 'Online Services'
    ");

        if (!empty($openemisRegistrationId)) {
            // Fetch all themes
            $themes = $this->fetchAll("SELECT * FROM `themes`");

            foreach ($themes as &$theme) {
                // Filter out numeric keys
                $theme = array_filter($theme, function($key) {
                    return !is_int($key);
                }, ARRAY_FILTER_USE_KEY);

                unset($theme['id']); // Remove the primary key to allow duplication
                $theme['config_item_id'] = $openemisRegistrationId['id'];
                $theme['created'] = date('Y-m-d H:i:s');

                // Prepare column names and values for the SQL insert
                $columns = implode(", ", array_keys($theme));
                $values = implode(', ', array_map(function ($v) {
                    if (is_null($v)) {
                        return 'NULL';
                    }
                    return is_string($v) ? "'" . addslashes($v) . "'" : $v;
                }, $theme));

                // Insert each new theme individually using SQL
                $this->execute("INSERT INTO `themes` ($columns) VALUES ($values)");
            }
        }
    }




    public function down()
    {
        $this->restoreTables();
    }

    /**
     * Restore the original tables from backups.
     *
     * @return void
     */
    private function restoreTables(): void
    {
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');

        $this->restoreTable('config_items', 'z_8951_config_items');
        $this->restoreTable('config_item_options', 'z_8951_config_item_options');
        if ($this->table('themes')->hasForeignKey('config_item_id')) {
            $this->execute("
            ALTER TABLE `themes` DROP FOREIGN KEY `fk_themes_config_item`
        ");
        }
        $this->restoreTable('themes', 'z_8951_themes'); // Restore themes table

        $this->execute('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Restore a single table from its backup.
     *
     * @param string $originalTable The name of the original table.
     * @param string $backupTable The name of the backup table.
     * @return void
     */
    private function restoreTable(string $originalTable, string $backupTable): void
    {
        if ($this->hasTable($backupTable)) {
            $this->execute("DROP TABLE IF EXISTS `$originalTable`");
            $this->execute("RENAME TABLE `$backupTable` TO `$originalTable`");
            $this->execute("DROP TABLE IF EXISTS `$backupTable`");
        }
    }
}
