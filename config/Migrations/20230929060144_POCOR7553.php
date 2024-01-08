<?php

use Phinx\Migration\AbstractMigration;

class POCOR7553 extends AbstractMigration
{
    public function up()
    {
        //creating backup
        $this->execute('DROP TABLE IF EXISTS `z_7553_security_functions`');
        $this->execute('CREATE TABLE `z_7553_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_7553_security_functions` SELECT * FROM `security_functions`');

        //getting parent_id value
        $row = $this->fetchRow("SELECT * FROM `security_functions` WHERE 
                                         `controller` = 'Directories' AND 
                                         `module` = 'Directory' AND 
                                         `name` = 'Overview' AND 
                                         `category` = 'General'");

        $parentId = $row['id'];

        //getting max order value
        $data = $this->fetchRow("SELECT  max(`order`) FROM `security_functions`");
        //inserting record
        $this->insert('security_functions', [
            'name' => 'Merge Users',
            'controller' => 'Directories',
            'module' => 'Directory',
            'category' => 'General',
            'parent_id' => $parentId,
            '_view' => NULL,
            '_edit' => NULL,
            '_add' => NULL,
            '_delete' => NULL,
            '_execute' => 'Directories.merge',
            'order' => $data[0] + 1,
            'visible' => 1,
            'description' => NULL,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);
    }

    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `z_7553_security_functions` TO `security_functions`');
    }
}
