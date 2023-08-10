<?php
use Migrations\AbstractMigration;

class POCOR7439 extends AbstractMigration
{
    public function up()
    {
        // Backup table
        $this->execute('CREATE TABLE `z_7439_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_7439_security_functions` SELECT * FROM `security_functions`');
        $parentRow = $this->fetchRow("SELECT * FROM `security_functions` WHERE `module` = 'Personal' AND `name` = 'Overview' AND `category` = 'General' and `controller`='Profiles'");
        $orderRow = $this->fetchRow("SELECT * FROM `security_functions` WHERE `module` = 'Personal' AND `name` = 'Timetables' AND `category` = 'Staff - Timetables' and `controller`='Profiles'");

        $data = [ 
            [
                'name' => 'Cases',
                'controller' => 'Profiles',
                'module' => 'Personal',
                'category' =>'Cases',
                'parent_id' =>$parentRow['id'],
                '_view' => 'Cases.index|Cases.view',
                '_edit' => 'Cases.edit',
                '_add' => 'Cases.add',
                '_delete' => 'Cases.remove',
                '_execute' => 'Cases.excel',
                'order' =>$orderRow['order']-1,
                'visible' =>1,
                'modified_user_id' =>2,
                'modified' => date('Y-m-d H:i:s'),
                'created_user_id' => 2,	
                'created' => date('Y-m-d H:i:s'),
            ]
        ];
        $this->insert('security_functions', $data);
    }
    public function down()
    { 
        // Restore table
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `z_7439_security_functions` TO `security_functions`');
    }
}
