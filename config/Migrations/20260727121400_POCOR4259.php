<?php
use Migrations\AbstractMigration;

class POCOR4259 extends AbstractMigration
{
    public function up()
    {
        // Backup security_functions table
        $this->execute('CREATE TABLE `zz_4259_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_4259_security_functions` SELECT * FROM `security_functions`');

        // set permissions
        $row = $this->fetchRow("
            SELECT MAX(`order`) AS max_order, MAX(`parent_id`) AS parent_id
            FROM `security_functions`
            WHERE `module` = 'Institutions'
              AND `category` = 'Students - General'
            ");
        $order = $row['max_order'] + 1;
        $parentId = $row['parent_id'];
        $record = [
            [
                'name' => 'Siblings', 'controller' => 'Students', 'module' => 'Institutions', 'category' => 'Students - General', 'parent_id' => $parentId,'_view' => 'Siblings.index|Siblings.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => $order, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ]
        ];
        $this->table('security_functions')->insert($record)->save();
        
    }

    public function down()
    {
        $this->execute("
            DELETE FROM `security_functions`
            WHERE `controller` = 'Students'
              AND `module` = 'Institutions'
              AND `category` = 'Students - General'
              AND `name` = 'Siblings'
        ");

        // Remove backup table
        $this->execute('DROP TABLE IF EXISTS `zz_4259_security_functions`');
    }
}