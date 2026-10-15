<?php
use Migrations\AbstractMigration;

/**
 * POCOR-9812: Reports > Custom > Add (create a custom report definition in the `reports` table)
 * gets its own permission, "Custom Report Setup", placed right after Reports > Custom.
 * The existing "Custom" permission (_add = CustomReports.add) now controls the Process button.
 */
class POCOR9812 extends AbstractMigration
{
    public function up(): void
    {
        $this->execute('CREATE TABLE IF NOT EXISTS `z_9812_security_functions` LIKE `security_functions`');
        $this->execute('INSERT IGNORE INTO `z_9812_security_functions` SELECT * FROM `security_functions`');

        // already added (e.g. the migration was re-run) - nothing to do
        $existing = $this->fetchRow("SELECT `id` FROM `security_functions` WHERE `controller` = 'Reports' AND `_add` = 'CustomReportSetups.add'");
        if (!empty($existing)) {
            return;
        }

        $custom = $this->fetchRow("SELECT `order` FROM `security_functions` WHERE `controller` = 'Reports' AND `module` = 'Reports' AND `_add` = 'CustomReports.add'");
        if (empty($custom)) {
            // without the Reports > Custom row there is no place to put the new one; stop rather than
            // shifting the order of every permission from 1
            throw new \RuntimeException('POCOR-9812: security function for Reports > Custom (_add = CustomReports.add) not found.');
        }
        $order = (int)$custom['order'] + 1;

        // make room directly after "Custom" so the order stays unique across modules
        $this->execute('UPDATE `security_functions` SET `order` = `order` + 1 WHERE `order` >= ' . $order);

        $this->table('security_functions')->insert([
            'name' => 'Custom Report Setup',
            'controller' => 'Reports',
            'module' => 'Reports',
            'category' => 'Reports',
            'parent_id' => -1,
            '_view' => null,
            '_edit' => null,
            '_add' => 'CustomReportSetups.add',
            '_delete' => null,
            '_execute' => null,
            'order' => $order,
            'visible' => 1,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ])->save();
    }

    public function down(): void
    {
        // remove only this row and close the gap, so later changes to security_functions are kept
        $row = $this->fetchRow("SELECT `id`, `order` FROM `security_functions` WHERE `controller` = 'Reports' AND `_add` = 'CustomReportSetups.add'");
        if (!empty($row)) {
            $this->execute('DELETE FROM `security_role_functions` WHERE `security_function_id` = ' . (int)$row['id']);
            $this->execute('DELETE FROM `security_functions` WHERE `id` = ' . (int)$row['id']);
            $this->execute('UPDATE `security_functions` SET `order` = `order` - 1 WHERE `order` > ' . (int)$row['order']);
        }
        $this->execute('DROP TABLE IF EXISTS `z_9812_security_functions`');
    }
}
