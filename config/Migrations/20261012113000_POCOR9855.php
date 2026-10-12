<?php
use Migrations\AbstractMigration;

/**
 * POCOR-9855
 * Add Institutions > General > Scanned permission 
 * can be granted access to Institution > Scanned 
 */
class POCOR9855 extends AbstractMigration
{
    public function up()
    {
        // Backup security_functions table
        $this->execute('CREATE TABLE IF NOT EXISTS `zz_9855_security_functions` LIKE `security_functions`');
        $this->execute('INSERT IGNORE INTO `zz_9855_security_functions` SELECT * FROM `security_functions`');

        $exists = $this->fetchRow("
            SELECT `id` FROM `security_functions`
            WHERE `controller` = 'Institutions'
              AND `module` = 'Institutions'
              AND `_view` LIKE '%Scanned.index%'
            ");
        if (!empty($exists)) {
            return;
        }

        $row = $this->fetchRow("
            SELECT MAX(`order`) AS max_order
            FROM `security_functions`
            WHERE `module` = 'Institutions'
              AND `category` = 'General'
            ");
        $order = ((int)($row['max_order'] ?? 0)) + 1;

        // Use the same parent as the Attachments
        $rowParent = $this->fetchRow("
            SELECT `parent_id` FROM `security_functions`
            WHERE `module` = 'Institutions'
              AND `controller` = 'Institutions'
              AND `category` = 'General'
              AND `name` = 'Attachments'
              LIMIT 1
            ");
        
        $parentId = $rowParent['parent_id'];
        
        $record = [
            [
                'name' => 'Scanned', 'controller' => 'Institutions', 'module' => 'Institutions', 'category' => 'General', 'parent_id' => $parentId, '_view' => 'Scanned.index|Scanned.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => $order, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ]
        ];
        $this->table('security_functions')->insert($record)->save();
    }

    public function down()
    {
        $this->execute("
            DELETE FROM `security_functions`
            WHERE `controller` = 'Institutions'
              AND `module` = 'Institutions'
              AND `category` = 'General'
              AND `name` = 'Scanned'
        ");

        // Remove backup table
        $this->execute('DROP TABLE IF EXISTS `zz_9855_security_functions`');
    }
}
