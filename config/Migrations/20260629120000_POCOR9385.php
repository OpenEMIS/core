<?php
use Migrations\AbstractMigration;

class POCOR9385 extends AbstractMigration
{
    public function up(): void
    {
        $this->backupTables();

        // Insert config_items rows //POCOR-9385: toggle + excluded roles config items
        // POCOR-9385: no hardcoded ids — `code` is UNIQUE and `id` is auto-increment.
        // Hardcoding ids collided with config_items master added after this branch forked
        // (1357/1358 became external_data_source_type / external_alert_service_im_telegram),
        // so INSERT IGNORE silently skipped both rows. Auto-increment + INSERT IGNORE-on-code is collision-proof.
        $this->execute("
            INSERT IGNORE INTO `config_items`
                (`name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`)
            VALUES
                ('Limit student addition to first grade only', 'restrict_student_creation', 'Add New Student', 'Limit student addition to first grade only', '0', '', '0', 1, 1, 'Dropdown', 'student_creation_toggle', NULL, NULL, 1, NOW()),
                ('Excluded Security Roles for Student Creation', 'student_creation_excluded_roles', 'Add New Student', 'Excluded Security Roles for Student Creation', '', '', '', 1, 1, 'Dropdown', 'database:Security.SecurityRoles', NULL, NULL, 1, NOW())
        "); //POCOR-9385: roles row rendered as chosenSelect by ConfigItemsTable::onUpdateFieldValue

        // Insert config_item_options for the toggle //POCOR-9385: Enabled/Disabled options
        $this->execute("
            INSERT IGNORE INTO `config_item_options` (`option_type`, `option`, `value`, `order`, `visible`)
            VALUES
                ('student_creation_toggle', 'Disabled', '0', 1, 1),
                ('student_creation_toggle', 'Enabled', '1', 2, 1)
        ");
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
