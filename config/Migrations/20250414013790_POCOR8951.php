<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR8951 extends AbstractMigration
{
    public function up()
    {
        $this->backupTables();
        $this->insertConfigItems();
        $this->insertConfigItemOptions();
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
          AND `code` = 'online_services'
          AND `type` = 'Online Services'
          AND `label` = 'Online Services'
    ");

        if (empty($existingRecord)) {
            $this->execute("
            INSERT INTO `config_items`
            (`id`, `name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`)
            VALUES
            (NULL, 'OpenEMIS Registration', 'online_services', 'Online Services', 'Online Services', '1', '', '1', 0, 1, 'Dropdown', 'online_services', NULL, NULL, 1, CURRENT_TIMESTAMP)
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

    public function down()
    {
        $this->restoreTables();
        $this->cleanupBackupTables();
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
