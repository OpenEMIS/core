<?php
use Migrations\AbstractMigration;

class POCOR9811 extends AbstractMigration
{
    public function up()
    {
        // Backup security_functions table
        $this->execute('CREATE TABLE `zz_9811_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_9811_security_functions` SELECT * FROM `security_functions`');

        $row = $this->fetchRow("
            SELECT MAX(`order`) AS max_order, MAX(`parent_id`) AS parent_id
            FROM `security_functions`
            WHERE `module` = 'Institutions'
              AND `category` = 'Students - Guardians'
            ");
        $order = $row['max_order'] + 1;
        $parentId = $row['parent_id'];
        $record = [
            [
                'name' => 'Student Guardian Profile', 'controller' => 'Students', 'module' => 'Institutions', 'category' => 'Students - Guardians', 'parent_id' => $parentId,'_view' => 'GuardianProfile.index|GuardianProfile.view', '_edit' => 'GuardianProfile.edit', '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => $order, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
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
              AND `category` = 'Students - Guardians'
              AND `name` = 'Student Guardian Profile'
        ");

        // Remove backup table
        $this->execute('DROP TABLE IF EXISTS `zz_9811_security_functions`');
    }
}