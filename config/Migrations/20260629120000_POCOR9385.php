<?php
use Migrations\AbstractMigration;

class POCOR9385 extends AbstractMigration
{
    public function up(): void
    {
        $this->backupTables();

        // Insert config_items row //POCOR-9385: single row — toggle + excluded roles
        // POCOR-9385: no hardcoded ids — `code` is UNIQUE and `id` is auto-increment.
        // Hardcoding ids collided with config_items master added after this branch forked
        // (1357/1358 became external_data_source_type / external_alert_service_im_telegram),
        // so INSERT IGNORE silently skipped the row. Auto-increment + INSERT IGNORE-on-code is collision-proof.
        $this->execute("
            INSERT IGNORE INTO `config_items`
                (`name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`)
            VALUES
                ('Limit student addition to first grade only', 'restrict_student_creation', 'Add New Student', 'Limit student addition to first grade only', '0', '', '0', 1, 1, 'Dropdown', 'student_creation_toggle', NULL, NULL, 1, NOW())
        "); //POCOR-9385: Excluded Security Roles is rendered on this SAME row's edit page via value_selection (ConfigItemsTable), not a separate config item

        // Insert config_item_options for the toggle //POCOR-9385: Enabled/Disabled options
        $this->execute("
            INSERT IGNORE INTO `config_item_options` (`option_type`, `option`, `value`, `order`, `visible`)
            VALUES
                ('student_creation_toggle', 'Disabled', '0', 1, 1),
                ('student_creation_toggle', 'Enabled', '1', 2, 1)
        ");

        // POCOR-9385: merge-forward for boxes that already ran the earlier version of this migration,
        // which inserted 'student_creation_excluded_roles' as its own row. Carry any saved value into
        // restrict_student_creation.value_selection, then drop the now-obsolete row. Safe no-op otherwise.
        $legacyRow = $this->fetchRow("SELECT `value` FROM `config_items` WHERE `code` = 'student_creation_excluded_roles'");
        if (!empty($legacyRow)) {
            if (!empty($legacyRow['value'])) {
                $quoted = $this->getAdapter()->getConnection()->quote($legacyRow['value']);
                $this->execute(
                    "UPDATE `config_items` SET `value_selection` = {$quoted} WHERE `code` = 'restrict_student_creation'"
                );
            }
            $this->execute("DELETE FROM `config_items` WHERE `code` = 'student_creation_excluded_roles'");
        }
    }

    public function down(): void
    {
        $this->restoreTables(); //POCOR-9385: restore config backups
    }

    private function backupTables(): void //POCOR-9385: backup before changes
    {
        $tables = ['config_items', 'config_item_options'];
        foreach ($tables as $t) {
            $b = 'z_9385_' . $t;
            if (!$this->hasTable($b)) {
                $this->execute('SET FOREIGN_KEY_CHECKS=0;');
                $this->execute("CREATE TABLE `{$b}` LIKE `{$t}`");
                $this->execute("INSERT INTO `{$b}` SELECT * FROM `{$t}`");
                $this->execute('SET FOREIGN_KEY_CHECKS=1;');
            }
        }
    }

    private function restoreTables(): void //POCOR-9385: restore from backup
    {
        $tables = ['config_items', 'config_item_options'];
        foreach ($tables as $t) {
            $b = 'z_9385_' . $t;
            if ($this->hasTable($b)) {
                $this->execute('SET FOREIGN_KEY_CHECKS=0;');
                $this->execute("DROP TABLE IF EXISTS `{$t}`");
                $this->execute("RENAME TABLE `{$b}` TO `{$t}`");
                $this->execute('SET FOREIGN_KEY_CHECKS=1;');
            }
        }
    }
}
